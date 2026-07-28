<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Room as RoomModel;
use App\Services\WebSocketService;

class RoomApiController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly WebSocketService $ws
    ) {
        parent::__construct($request, $response, $db);
    }

    public function index(): void
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $rooms = (new RoomModel($this->db, $this->ws))->listRooms([
            'category' => $this->request->get('category'),
            'type'     => $this->request->get('type'),
            'search'   => $this->request->get('q'),
            'language' => $this->request->get('language'),
        ], $page, 20);
        $this->success($rooms);
    }

    public function show(string $id): void
    {
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) { $this->json(['error' => 'Room not found'], 404); return; }
        $room['participants']     = (new RoomModel($this->db, $this->ws))->participants((int) $id, true);
        $room['recent_messages']  = (new RoomModel($this->db, $this->ws))->messages((int) $id, 30);
        $this->success($room);
    }

    public function join(string $id): void
    {
        $user = $this->user();
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) { $this->json(['error' => 'Room not found'], 404); return; }
        if ($room['is_locked'] && (int) $room['owner_id'] !== (int) $user['id']) {
            $this->json(['error' => 'Room is locked'], 403); return;
        }
        if ($room['type'] === 'password' && $this->request->post('password') !== null) {
            if (!password_verify((string) $this->request->post('password'), (string) $room['password'])) {
                $this->json(['error' => 'Wrong password'], 403); return;
            }
        }
        (new RoomModel($this->db, $this->ws))->joinRoom((int) $id, (int) $user['id'], null, (string) $this->request->header('x-connection-id'));
        $this->ws->emit((int) $id, 'user_joined', ['user' => $this->auth->publicUser()]);
        $this->success(['room' => $room, 'state' => $this->ws->getRoomState((int) $id)], 'Joined');
    }

    public function leave(string $id): void
    {
        $user = $this->user();
        (new RoomModel($this->db, $this->ws))->leaveRoom((int) $id, (int) $user['id']);
        $this->ws->emit((int) $id, 'user_left', ['user_id' => $user['id']]);
        $this->success(null, 'Left');
    }

    public function seat(string $id): void
    {
        $data = $this->validate([
            'seat_index' => 'required|integer|min:0|max:15',
            'action'     => 'in:take,leave,swap,lock,unlock',
        ]);
        $user = $this->user();
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) { $this->json(['error' => 'Room not found'], 404); return; }
        $model = new RoomModel($this->db, $this->ws);
        $isOwner = (int) $room['owner_id'] === (int) $user['id'];
        $isMod   = $model->isModerator((int) $id, (int) $user['id']) || $isOwner;
        switch ($data['action']) {
            case 'take':
                if (!$isOwner && !$isMod) { $this->json(['error' => 'Permission denied'], 403); return; }
                $model->setSeat((int) $id, (int) $user['id'], (int) $data['seat_index'], 'speaker');
                $model->incrementMic((int) $id);
                $this->ws->emit((int) $id, 'seat_taken', ['user_id' => $user['id'], 'seat' => (int) $data['seat_index']]);
                break;
            case 'leave':
                $model->setSeat((int) $id, (int) $user['id'], null, 'listener');
                $model->decrementMic((int) $id);
                $this->ws->emit((int) $id, 'seat_freed', ['user_id' => $user['id']]);
                break;
            case 'lock':
                if (!$isMod) { $this->json(['error' => 'Permission denied'], 403); return; }
                $model->lockSeat((int) $id, (int) $data['seat_index'], true);
                $this->ws->emit((int) $id, 'seat_locked', ['seat' => (int) $data['seat_index']]);
                break;
            case 'unlock':
                if (!$isMod) { $this->json(['error' => 'Permission denied'], 403); return; }
                $model->lockSeat((int) $id, (int) $data['seat_index'], false);
                $this->ws->emit((int) $id, 'seat_unlocked', ['seat' => (int) $data['seat_index']]);
                break;
        }
        $this->success(null, 'OK');
    }

    public function mic(string $id): void
    {
        $data = $this->validate([
            'action' => 'required|in:mute,unmute,kick',
            'target' => 'integer',
        ]);
        $user = $this->user();
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) { $this->json(['error' => 'Room not found'], 404); return; }
        $model = new RoomModel($this->db, $this->ws);
        $isOwner = (int) $room['owner_id'] === (int) $user['id'];
        $isMod   = $model->isModerator((int) $id, (int) $user['id']) || $isOwner;
        $target  = (int) ($data['target'] ?? $user['id']);
        if ($data['action'] === 'kick' && !$isMod) { $this->json(['error' => 'Permission denied'], 403); return; }
        if ($data['action'] === 'mute' && !$isMod && $target !== (int) $user['id']) { $this->json(['error' => 'Permission denied'], 403); return; }
        switch ($data['action']) {
            case 'mute':
                $model->mute((int) $id, $target, true);
                $this->ws->emit((int) $id, 'user_muted', ['user_id' => $target]);
                break;
            case 'unmute':
                $model->mute((int) $id, $target, false);
                $this->ws->emit((int) $id, 'user_unmuted', ['user_id' => $target]);
                break;
            case 'kick':
                $model->kick((int) $id, $target);
                break;
        }
        $this->success(null, 'OK');
    }

    public function hand(string $id): void
    {
        $data = $this->validate(['action' => 'required|in:raise,lower,accept,reject', 'target' => 'integer']);
        $user = $this->user();
        $model = new RoomModel($this->db, $this->ws);
        $room = $model->find((int) $id);
        if (!$room) { $this->json(['error' => 'Room not found'], 404); return; }
        $target = (int) ($data['target'] ?? $user['id']);
        switch ($data['action']) {
            case 'raise': $model->raiseHand((int) $id, (int) $user['id'], true); $this->ws->emit((int) $id, 'hand_raised', ['user_id' => $user['id']]); break;
            case 'lower': $model->raiseHand((int) $id, (int) $user['id'], false); $this->ws->emit((int) $id, 'hand_lowered', ['user_id' => $user['id']]); break;
            case 'accept': $model->setSeat((int) $id, $target, $this->findFreeSeat((int) $id), 'speaker'); $this->ws->emit((int) $id, 'seat_accepted', ['user_id' => $target]); break;
            case 'reject': $model->raiseHand((int) $id, $target, false); $this->ws->emit((int) $id, 'seat_rejected', ['user_id' => $target]); break;
        }
        $this->success(null, 'OK');
    }

    public function chat(string $id): void
    {
        $data = $this->validate([
            'content' => 'required|string|max:500',
            'type'    => 'in:text,emoji,gift,system',
        ]);
        $user = $this->user();
        $model = new RoomModel($this->db, $this->ws);
        $id = (int) $id;
        $msgId = $model->addMessage($id, (int) $user['id'], $data['content'], $data['type'] ?? 'text');
        $msg = $this->db->fetchOne(
            'SELECT m.*, u.username, u.display_name, u.avatar, u.level FROM room_messages m JOIN users u ON u.id = m.user_id WHERE m.id = :id',
            ['id' => $msgId]
        );
        $this->ws->emit($id, 'chat_message', ['message' => $msg]);
        $this->success($msg, 'Sent');
    }

    public function messages(string $id): void
    {
        $rows = (new RoomModel($this->db, $this->ws))->messages((int) $id, 50, $this->request->get('before') ? (int) $this->request->get('before') : null);
        $this->success($rows);
    }

    public function participants(string $id): void
    {
        $rows = (new RoomModel($this->db, $this->ws))->participants((int) $id, true);
        $this->success($rows);
    }

    /**
     * WebRTC signalling relay: store offer/answer/ice candidates.
     */
    public function signaling(string $id): void
    {
        $data = $this->validate([
            'to'      => 'required|integer',
            'type'    => 'required|in:offer,answer,ice,bye',
            'payload' => 'string',
        ]);
        $user = $this->user();
        $payload = [
            'from'    => (int) $user['id'],
            'to'      => (int) $data['to'],
            'type'    => $data['type'],
            'payload' => $data['payload'] ?? null,
            'room_id' => (int) $id,
        ];
        $this->ws->emit((int) $id, 'webrtc_signal', $payload);
        $this->success(null, 'Signalled');
    }

    private function findFreeSeat(int $roomId): ?int
    {
        $occupied = $this->db->fetchAll(
            'SELECT seat_index FROM room_participants WHERE room_id = :r AND left_at IS NULL AND seat_index IS NOT NULL',
            ['r' => $roomId]
        );
        $used = array_map(fn($r) => (int) $r['seat_index'], $occupied);
        $room = (new RoomModel($this->db, $this->ws))->find($roomId);
        $max  = (int) ($room['max_seats'] ?? 8);
        for ($i = 0; $i < $max; $i++) {
            if (!in_array($i, $used, true)) return $i;
        }
        return null;
    }
}
