<?php
/**
 * VoiceChat — WebSocket Server
 * 
 * Standalone PHP WebSocket server for real-time:
 *  - Room participants (presence, mic seats, mute, hand raise)
 *  - WebRTC signalling (offer/answer/ice)
 *  - Chat messages
 *  - Gift animations
 *  - Private messages
 * 
 * Run with: php public/ws.php
 * 
 * Protocol: JSON frames
 * Inbound:  { type: "hello|offer|answer|ice|chat|...", payload: {...} }
 * Outbound: { type: "participants|message|signal|...", data: {...} }
 * 
 * Architecture:
 *  - In-memory state (rooms + connections) for fast routing
 *  - MySQL is the source of truth (joined/left, messages, gifts)
 *  - Polls `ws_events` table for events written by the PHP app
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Services\JwtService;

// ===== Bootstrap =====
$app = Application::bootstrap(dirname(__DIR__));
$cfg = $app->getConfig('websocket', []);
$db  = $app->getDb();
$jwt = new JwtService($db, $app->getConfig('jwt', []));
$logger = $app->getService('logger');

$host = $cfg['host'] ?? '0.0.0.0';
$port = (int) ($cfg['port'] ?? 8080);

$logger->info("WebSocket server starting on $host:$port");

// ===== In-memory state =====
$rooms = [];   // roomId => [ connectionId => conn ]
$conns = [];   // connectionId => Connection
$connUser = []; // connectionId => userId
$userConns = []; // userId => [connectionId, ...]
$userSockets = []; // userId => [socket, ...]

// ===== Helpers =====
function guid(): string { return bin2hex(random_bytes(8)); }

function send_frame($socket, array $data): void
{
    if (!$socket || !is_resource($socket)) return;
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
    $frame = pack('CC', 0x81, strlen($payload) < 126 ? 0x80 | strlen($payload) : (strlen($payload) < 65536 ? 0x80 | 126 : 0x80 | 127));
    if (strlen($payload) < 126) {
        // already packed
    } elseif (strlen($payload) < 65536) {
        $frame .= pack('n', strlen($payload));
    } else {
        $frame .= pack('J', strlen($payload));
    }
    $frame .= $payload;
    @socket_write($socket, $frame);
}

function close_frame($socket, int $code = 1000): void
{
    if (!$socket || !is_resource($socket)) return;
    $payload = pack('n', $code) . 'bye';
    $frame = pack('CC', 0x88, 0x80 | strlen($payload)) . $payload;
    @socket_write($socket, $frame);
    @socket_close($socket);
}

function broadcast_room(int $roomId, array $data, ?int $exceptConnId = null): void
{
    global $rooms, $conns;
    if (!isset($rooms[$roomId])) return;
    foreach ($rooms[$roomId] as $cid => $_) {
        if ($cid === $exceptConnId) continue;
        $conn = $conns[$cid] ?? null;
        if ($conn) send_frame($conn['socket'], $data);
    }
}

function send_to_user(int $userId, array $data): void
{
    global $userSockets;
    foreach ($userSockets[$userId] ?? [] as $sock) {
        send_frame($sock, $data);
    }
}

function build_participants_list(int $roomId): array
{
    global $db;
    $rows = $db->fetchAll(
        'SELECT rp.user_id, rp.seat_index, rp.role, rp.is_muted, rp.is_hand_raised,
                u.username, u.display_name, u.avatar, u.level, u.is_verified, u.online_status
         FROM room_participants rp
         JOIN users u ON u.id = rp.user_id
         WHERE rp.room_id = :r AND rp.left_at IS NULL
         ORDER BY rp.seat_index IS NULL, rp.seat_index ASC, rp.joined_at ASC',
        ['r' => $roomId]
    );
    return $rows;
}

// ===== Socket server =====
$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, $host, $port);
socket_listen($server, 128);
socket_set_nonblock($server);

$logger->info("WebSocket server listening on ws://$host:$port");

$readSocks = [$server];
$writeSocks = [];
$exceptSocks = [];
$lastEventPoll = 0;

// ===== Event poller (ws_events) =====
function poll_events(int $afterId, int $limit = 200): array
{
    global $db;
    return $db->fetchAll(
        'SELECT * FROM ws_events WHERE id > :id AND delivered = 0 ORDER BY id ASC LIMIT ' . (int) $limit,
        ['id' => $afterId]
    );
}

function mark_events_delivered(array $ids): void
{
    global $db;
    if (!$ids) return;
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->pdo()->prepare("UPDATE ws_events SET delivered = 1, delivered_at = NOW() WHERE id IN ($place)");
    $stmt->execute($ids);
}

$lastEventId = 0;

// ===== Frame decoder =====
function decode_frame($socket): ?array
{
    $header = @socket_read($socket, 2);
    if ($header === false || strlen($header) < 2) return null;
    $b1 = ord($header[0]);
    $b2 = ord($header[1]);
    $opcode = $b1 & 0x0F;
    if ($opcode === 0x8) return ['close' => true];
    $masked = ($b2 & 0x80) !== 0;
    $len = $b2 & 0x7F;
    if ($len === 126) {
        $ext = @socket_read($socket, 2);
        if (strlen($ext) < 2) return null;
        $len = unpack('n', $ext)[1];
    } elseif ($len === 127) {
        $ext = @socket_read($socket, 8);
        if (strlen($ext) < 8) return null;
        $len = unpack('J', $ext)[1];
    }
    $mask = null;
    if ($masked) {
        $mask = @socket_read($socket, 4);
        if (strlen($mask) < 4) return null;
    }
    $payload = '';
    while (strlen($payload) < $len) {
        $chunk = @socket_read($socket, $len - strlen($payload));
        if ($chunk === false || $chunk === '') break;
        $payload .= $chunk;
    }
    if ($masked && $mask) {
        $out = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $out .= $payload[$i] ^ $mask[$i % 4];
        }
        $payload = $out;
    }
    return ['opcode' => $opcode, 'payload' => $payload];
}

// ===== Main loop =====
while (true) {
    // Listen for new connections / data
    $changedSocks = $readSocks;
    $null = null;
    @socket_select($changedSocks, $writeSocks, $exceptSocks, 0, 200000);

    foreach ($changedSocks as $sock) {
        if ($sock === $server) {
            $client = @socket_accept($server);
            if ($client) {
                socket_set_nonblock($client);
                $readSocks[] = $client;
            }
            continue;
        }
        $frame = decode_frame($sock);
        if ($frame === null) continue;
        if (!empty($frame['close'])) {
            @socket_close($sock);
            $key = array_search($sock, $readSocks, true);
            if ($key !== false) unset($readSocks[$key]);
            continue;
        }
        $payload = $frame['payload'] ?? '';
        if ($payload === '') continue;
        $msg = @json_decode($payload, true);
        if (!is_array($msg)) continue;

        // Find connection id
        $connId = null;
        foreach ($conns as $cid => $c) {
            if ($c['socket'] === $sock) { $connId = $cid; break; }
        }
        if (!$connId) {
            // Handshake / first frame — accept hello
            if (($msg['type'] ?? '') === 'hello') {
                $token = $msg['token'] ?? '';
                $claims = $jwt->tryValidate($token);
                if (!$claims || empty($claims['sub'])) {
                    send_frame($sock, ['type' => 'error', 'message' => 'Invalid token']);
                    close_frame($sock);
                    $key = array_search($sock, $readSocks, true);
                    if ($key !== false) unset($readSocks[$key]);
                    continue;
                }
                $userId = (int) $claims['sub'];
                $connId = guid();
                $conns[$connId] = [
                    'socket' => $sock,
                    'user_id' => $userId,
                    'room_id' => isset($msg['room_id']) ? (int) $msg['room_id'] : null,
                    'channel' => $msg['channel'] ?? null,
                    'with_user' => isset($msg['with']) ? (int) $msg['with'] : null,
                    'connected_at' => time(),
                ];
                $connUser[$connId] = $userId;
                $userSockets[$userId][] = $sock;
                $userConns[$userId][] = $connId;

                if (!empty($msg['room_id'])) {
                    $roomId = (int) $msg['room_id'];
                    $rooms[$roomId][$connId] = $conns[$connId];
                    $participants = build_participants_list($roomId);
                    send_frame($sock, ['type' => 'participants', 'data' => $participants]);
                    broadcast_room($roomId, [
                        'type' => 'participant_joined',
                        'data' => $participants[array_search($userId, array_column($participants, 'user_id'))] ?? null,
                    ], $connId);
                    // Update DB
                    $db->query('UPDATE room_participants SET connection_id = :c, last_active_at = NOW() WHERE room_id = :r AND user_id = :u AND left_at IS NULL',
                        ['c' => $connId, 'r' => $roomId, 'u' => $userId]);
                    $db->query('UPDATE users SET online_status = "online", last_seen_at = NOW() WHERE id = :u', ['u' => $userId]);
                }
                send_frame($sock, ['type' => 'ready', 'connection_id' => $connId]);
            } else {
                send_frame($sock, ['type' => 'error', 'message' => 'Hello required first']);
            }
            continue;
        }

        // Authenticated message handling
        $conn = $conns[$connId];
        $userId = (int) $conn['user_id'];
        $roomId = (int) ($conn['room_id'] ?? 0);

        switch ($msg['type'] ?? '') {
            case 'ping':
                send_frame($sock, ['type' => 'pong', 'ts' => microtime(true)]);
                break;

            case 'chat':
                if ($roomId <= 0) break;
                $content = trim((string) ($msg['content'] ?? ''));
                if ($content === '') break;
                $msgId = $db->insert('room_messages', [
                    'room_id' => $roomId, 'user_id' => $userId, 'type' => 'text', 'content' => $content,
                ]);
                $row = $db->fetchOne(
                    'SELECT m.*, u.username, u.display_name, u.avatar, u.level FROM room_messages m JOIN users u ON u.id = m.user_id WHERE m.id = :id',
                    ['id' => $msgId]
                );
                broadcast_room($roomId, ['type' => 'chat_message', 'message' => $row]);
                break;

            case 'offer':
            case 'answer':
            case 'ice':
                $to = (int) ($msg['to'] ?? 0);
                if ($to <= 0) break;
                send_to_user($to, [
                    'type'     => $msg['type'],
                    'from'     => $userId,
                    'payload'  => $msg['payload'] ?? null,
                    'room_id'  => $roomId,
                ]);
                break;

            case 'mute':
            case 'unmute':
                $db->update('room_participants', ['is_muted' => $msg['type'] === 'mute' ? 1 : 0],
                    'room_id = :r AND user_id = :u AND left_at IS NULL', ['r' => $roomId, 'u' => $userId]);
                broadcast_room($roomId, ['type' => $msg['type'] === 'mute' ? 'user_muted' : 'user_unmuted', 'user_id' => $userId]);
                break;

            case 'hand_raise':
                $action = $msg['action'] ?? 'raise';
                $db->update('room_participants', ['is_hand_raised' => $action === 'raise' ? 1 : 0],
                    'room_id = :r AND user_id = :u AND left_at IS NULL', ['r' => $roomId, 'u' => $userId]);
                broadcast_room($roomId, ['type' => $action === 'raise' ? 'hand_raised' : 'hand_lowered', 'user_id' => $userId]);
                break;

            case 'speaking_start':
            case 'speaking_stop':
                broadcast_room($roomId, ['type' => $msg['type'], 'user_id' => $userId]);
                break;

            case 'leave_room':
                if ($roomId > 0) {
                    $db->update('room_participants', ['left_at' => date('Y-m-d H:i:s')],
                        'room_id = :r AND user_id = :u AND left_at IS NULL', ['r' => $roomId, 'u' => $userId]);
                    unset($rooms[$roomId][$connId]);
                    broadcast_room($roomId, ['type' => 'user_left', 'user_id' => $userId]);
                    send_frame($sock, ['type' => 'left', 'room_id' => $roomId]);
                }
                break;

            case 'private_message':
                $to = (int) ($msg['to'] ?? 0);
                $content = trim((string) ($msg['content'] ?? ''));
                if ($to > 0 && $content !== '') {
                    $id = $db->insert('messages', [
                        'sender_id' => $userId, 'receiver_id' => $to,
                        'type' => $msg['message_type'] ?? 'text', 'content' => $content,
                        'media_url' => $msg['media_url'] ?? null,
                    ]);
                    $row = $db->fetchOne('SELECT * FROM messages WHERE id = :id', ['id' => $id]);
                    send_to_user($to, ['type' => 'message', 'data' => $row]);
                    send_frame($sock, ['type' => 'message_sent', 'data' => $row]);
                }
                break;

            case 'typing':
                $to = (int) ($conn['with_user'] ?? 0);
                if ($to > 0) {
                    send_to_user($to, ['type' => 'typing', 'from' => $userId]);
                }
                break;

            case 'gift':
                $to = (int) ($msg['to'] ?? 0);
                $giftId = (int) ($msg['gift_id'] ?? 0);
                if ($to > 0 && $giftId > 0 && $roomId > 0) {
                    broadcast_room($roomId, [
                        'type' => 'gift_received',
                        'data' => [
                            'gift_id' => $giftId, 'from' => $userId, 'to' => $to,
                            'room_id' => $roomId, 'quantity' => $msg['quantity'] ?? 1,
                        ],
                    ]);
                }
                break;
        }
    }

    // Cleanup disconnected
    foreach ($conns as $cid => $c) {
        if (!is_resource($c['socket'])) {
            $userId = (int) $c['user_id'];
            $roomId = (int) ($c['room_id'] ?? 0);
            if ($roomId > 0 && isset($rooms[$roomId][$cid])) {
                unset($rooms[$roomId][$cid]);
                $db->update('room_participants', ['left_at' => date('Y-m-d H:i:s')],
                    'room_id = :r AND user_id = :u AND left_at IS NULL', ['r' => $roomId, 'u' => $userId]);
                broadcast_room($roomId, ['type' => 'user_left', 'user_id' => $userId]);
            }
            if (isset($userSockets[$userId])) {
                $key = array_search($c['socket'], $userSockets[$userId], true);
                if ($key !== false) unset($userSockets[$userId][$key]);
            }
            if (empty($userSockets[$userId])) {
                $db->query('UPDATE users SET online_status = "offline", last_seen_at = NOW() WHERE id = :u', ['u' => $userId]);
                unset($userSockets[$userId]);
            }
            if (isset($userConns[$userId])) {
                $key = array_search($cid, $userConns[$userId], true);
                if ($key !== false) unset($userConns[$userId][$key]);
            }
            unset($conns[$cid], $connUser[$cid]);
        }
    }

    // Poll DB events every 1s
    if (time() - $lastEventPoll >= 1) {
        $events = poll_events($lastEventId);
        foreach ($events as $ev) {
            $payload = is_string($ev['payload']) ? json_decode($ev['payload'], true) : $ev['payload'];
            $rid = (int) ($ev['room_id'] ?? 0);
            if ($rid > 0) {
                broadcast_room($rid, ['type' => $ev['event'], 'data' => $payload, 'from' => (int)($ev['user_id'] ?? 0)]);
            } elseif (!empty($ev['user_id'])) {
                send_to_user((int)$ev['user_id'], ['type' => $ev['event'], 'data' => $payload]);
            }
        }
        if ($events) {
            $ids = array_map(fn($e) => (int)$e['id'], $events);
            mark_events_delivered($ids);
            $lastEventId = (int) end($events)['id'];
        }
        $lastEventPoll = time();
    }
}
