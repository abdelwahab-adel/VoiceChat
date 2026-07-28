<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\WebSocketService;
use Ramsey\Uuid\Uuid;

class Room extends Model
{
    protected string $table = 'rooms';
    protected array $fillable = [
        'uuid','name','slug','description','cover','owner_id','agency_id','type','password',
        'language','country','category','tags','max_seats','max_listeners','is_locked',
        'is_recorded','is_featured','auto_mic_accept','background_music','status',
        'started_at','ended_at','settings',
    ];
    protected array $casts = [
        'tags' => 'json',
        'settings' => 'json',
        'is_locked' => 'bool',
        'is_recorded' => 'bool',
        'is_featured' => 'bool',
        'auto_mic_accept' => 'bool',
        'max_seats' => 'int',
        'max_listeners' => 'int',
        'current_listeners' => 'int',
        'mic_count' => 'int',
        'total_time' => 'int',
    ];

    public function __construct(
        \App\Core\Database $db,
        private readonly WebSocketService $ws
    ) {
        parent::__construct($db);
    }

    public function createRoom(int $ownerId, array $data): string
    {
        $slug = $this->uniqueSlug($data['name'] ?? 'room');
        $id = $this->create([
            'uuid'         => Uuid::uuid4()->toString(),
            'name'         => $data['name'],
            'slug'         => $slug,
            'description'  => $data['description'] ?? null,
            'cover'        => $data['cover'] ?? null,
            'owner_id'     => $ownerId,
            'agency_id'    => $data['agency_id'] ?? null,
            'type'         => $data['type'] ?? 'public',
            'password'     => !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : null,
            'language'     => $data['language'] ?? 'en',
            'country'      => $data['country'] ?? null,
            'category'     => $data['category'] ?? 'general',
            'tags'         => $data['tags'] ?? [],
            'max_seats'    => (int) ($data['max_seats'] ?? 8),
            'status'       => 'active',
            'started_at'   => date('Y-m-d H:i:s'),
        ]);
        // Owner joins as admin
        $this->db->insert('room_participants', [
            'room_id' => $id, 'user_id' => $ownerId, 'role' => 'owner', 'seat_index' => 0,
        ]);
        $this->refreshState((int) $id);
        return $id;
    }

    public function joinRoom(int $roomId, int $userId, ?int $seatIndex = null, ?string $connectionId = null): int
    {
        // Leave previous active
        $this->db->update('room_participants', ['left_at' => date('Y-m-d H:i:s')], 'user_id = :u AND left_at IS NULL', ['u' => $userId]);
        $id = $this->db->insert('room_participants', [
            'room_id' => $roomId,
            'user_id' => $userId,
            'role' => 'listener',
            'seat_index' => $seatIndex,
            'connection_id' => $connectionId,
        ]);
        $this->db->query('UPDATE rooms SET current_listeners = current_listeners + 1 WHERE id = :r', ['r' => $roomId]);
        $this->refreshState($roomId);
        return (int) $id;
    }

    public function leaveRoom(int $roomId, int $userId): void
    {
        $this->db->update('room_participants', ['left_at' => date('Y-m-d H:i:s')], 'room_id = :r AND user_id = :u AND left_at IS NULL', ['r' => $roomId, 'u' => $userId]);
        $this->db->query('UPDATE rooms SET current_listeners = GREATEST(0, current_listeners - 1) WHERE id = :r', ['r' => $roomId]);
        $this->refreshState($roomId);
    }

    public function setSeat(int $roomId, int $userId, ?int $seatIndex, string $role = 'speaker'): bool
    {
        return $this->db->update('room_participants', [
            'seat_index' => $seatIndex,
            'role' => $role,
        ], 'room_id = :r AND user_id = :u AND left_at IS NULL', ['r' => $roomId, 'u' => $userId]) > 0;
    }

    public function raiseHand(int $roomId, int $userId, bool $raised): bool
    {
        return $this->db->update('room_participants', [
            'is_hand_raised' => $raised ? 1 : 0,
        ], 'room_id = :r AND user_id = :u AND left_at IS NULL', ['r' => $roomId, 'u' => $userId]) > 0;
    }

    public function mute(int $roomId, int $userId, bool $muted): bool
    {
        return $this->db->update('room_participants', [
            'is_muted' => $muted ? 1 : 0,
        ], 'room_id = :r AND user_id = :u AND left_at IS NULL', ['r' => $roomId, 'u' => $userId]) > 0;
    }

    public function lockSeat(int $roomId, int $seatIndex, bool $locked): bool
    {
        return $this->db->update('room_participants', [
            'is_locked' => $locked ? 1 : 0,
        ], 'room_id = :r AND seat_index = :s AND left_at IS NULL', ['r' => $roomId, 's' => $seatIndex]) > 0;
    }

    public function kick(int $roomId, int $userId): bool
    {
        $this->leaveRoom($roomId, $userId);
        $this->ws->emit($roomId, 'user_kicked', ['user_id' => $userId]);
        return true;
    }

    public function ban(int $roomId, int $userId, int $byUserId): bool
    {
        $this->db->insert('blocks', [
            'user_id' => $roomId . ':' . $userId, 'blocked_id' => $userId, // pseudo
        ]);
        // Use a dedicated table later
        $this->leaveRoom($roomId, $userId);
        $this->ws->emit($roomId, 'user_banned', ['user_id' => $userId, 'by' => $byUserId]);
        return true;
    }

    public function participants(int $roomId, bool $onlyActive = true): array
    {
        $sql = 'SELECT rp.*, u.id as user_id, u.uuid, u.username, u.display_name, u.avatar, u.level, u.is_verified, u.online_status, u.vip_level
                FROM room_participants rp
                JOIN users u ON u.id = rp.user_id
                WHERE rp.room_id = :r';
        $params = ['r' => $roomId];
        if ($onlyActive) $sql .= ' AND rp.left_at IS NULL';
        $sql .= ' ORDER BY rp.seat_index IS NULL, rp.seat_index ASC, rp.joined_at ASC';
        return $this->db->fetchAll($sql, $params);
    }

    public function messages(int $roomId, int $limit = 50, ?int $beforeId = null): array
    {
        $sql = 'SELECT m.*, u.username, u.display_name, u.avatar, u.level
                FROM room_messages m JOIN users u ON u.id = m.user_id
                WHERE m.room_id = :r AND m.is_deleted = 0';
        $params = ['r' => $roomId];
        if ($beforeId) {
            $sql .= ' AND m.id < :bid';
            $params['bid'] = $beforeId;
        }
        $sql .= ' ORDER BY m.id DESC LIMIT ' . (int) $limit;
        $rows = $this->db->fetchAll($sql, $params);
        return array_reverse($rows);
    }

    public function addMessage(int $roomId, int $userId, string $content, string $type = 'text', array $data = []): int
    {
        return (int) $this->db->insert('room_messages', [
            'room_id' => $roomId,
            'user_id' => $userId,
            'type'    => $type,
            'content' => $content,
            'data'    => $data ? json_encode($data) : null,
        ]);
    }

    public function isModerator(int $roomId, int $userId): bool
    {
        return (bool) $this->db->fetchValue(
            'SELECT id FROM room_moderators WHERE room_id = :r AND user_id = :u LIMIT 1',
            ['r' => $roomId, 'u' => $userId]
        );
    }

    public function addModerator(int $roomId, int $userId, int $byUserId, array $permissions = []): bool
    {
        if ($this->isModerator($roomId, $userId)) return false;
        $this->db->insert('room_moderators', [
            'room_id'    => $roomId,
            'user_id'    => $userId,
            'granted_by' => $byUserId,
            'permissions'=> $permissions ?: null,
        ]);
        return true;
    }

    public function removeModerator(int $roomId, int $userId): bool
    {
        return $this->db->delete('room_moderators', 'room_id = :r AND user_id = :u', ['r' => $roomId, 'u' => $userId]) > 0;
    }

    public function listRooms(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['type'])) {
            $where[] = 'r.type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'r.category = :cat';
            $params['cat'] = $filters['category'];
        }
        if (!empty($filters['language'])) {
            $where[] = 'r.language = :lang';
            $params['lang'] = $filters['language'];
        }
        if (!empty($filters['agency_id'])) {
            $where[] = 'r.agency_id = :ag';
            $params['ag'] = $filters['agency_id'];
        }
        if (!empty($filters['featured'])) {
            $where[] = 'r.is_featured = 1';
        }
        if (!empty($filters['search'])) {
            $where[] = '(r.name LIKE :s OR r.description LIKE :s)';
            $params['s'] = '%' . $filters['search'] . '%';
        }
        $where[] = 'r.status = "active"';

        $whereSql = implode(' AND ', $where);
        $offset = max(0, ($page - 1) * $perPage);
        $total = (int) $this->db->fetchValue("SELECT COUNT(*) FROM rooms r WHERE {$whereSql}", $params);
        $rows = $this->db->fetchAll(
            "SELECT r.*, u.username as owner_username, u.display_name as owner_display_name, u.avatar as owner_avatar
             FROM rooms r JOIN users u ON u.id = r.owner_id
             WHERE {$whereSql}
             ORDER BY r.is_featured DESC, r.current_listeners DESC, r.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return [
            'data' => $rows,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function refreshState(int $roomId): void
    {
        $room = $this->find($roomId);
        $participants = $this->participants($roomId, true);
        $this->ws->setRoomState($roomId, [
            'room_id'   => $roomId,
            'mic_count' => $room['mic_count'] ?? 0,
            'max_seats' => (int) ($room['max_seats'] ?? 8),
            'listeners' => (int) ($room['current_listeners'] ?? 0),
            'is_locked' => (bool) ($room['is_locked'] ?? false),
            'updated_at'=> date('c'),
        ]);
    }

    public function uniqueSlug(string $name): string
    {
        $base = \slugify($name) ?: 'room';
        $slug = $base;
        $i = 1;
        while ($this->findBy('slug', $slug)) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public function incrementMic(int $roomId): void
    {
        $this->db->query('UPDATE rooms SET mic_count = LEAST(mic_count + 1, max_seats) WHERE id = :r', ['r' => $roomId]);
        $this->refreshState($roomId);
    }

    public function decrementMic(int $roomId): void
    {
        $this->db->query('UPDATE rooms SET mic_count = GREATEST(0, mic_count - 1) WHERE id = :r', ['r' => $roomId]);
        $this->refreshState($roomId);
    }
}
