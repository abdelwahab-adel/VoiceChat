<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * WebSocket Signalling Service.
 * 
 * Records room state and signalling events in MySQL so
 * that the WebSocket server (running as a separate process)
 * can read & broadcast.
 * 
 * The server process polls `ws_events` and `ws_room_state`
 * to detect changes since its last tick.
 */
final class WebSocketService
{
    public function __construct(private readonly Database $db) {}

    /**
     * Record a WebSocket event for a room.
     */
    public function emit(int $roomId, string $event, array $payload, ?int $userId = null): string
    {
        return $this->db->insert('ws_events', [
            'room_id'    => $roomId,
            'user_id'    => $userId,
            'event'      => $event,
            'payload'    => json_encode($payload),
        ]);
    }

    /**
     * Update the live state snapshot for a room.
     */
    public function setRoomState(int $roomId, array $state): void
    {
        $row = $this->db->fetchOne('SELECT id FROM ws_room_state WHERE room_id = :r LIMIT 1', ['r' => $roomId]);
        $payload = json_encode($state);
        if ($row) {
            $this->db->update('ws_room_state',
                ['state' => $payload, 'updated_at' => date('Y-m-d H:i:s')],
                'room_id = :r',
                ['r' => $roomId]
            );
        } else {
            $this->db->insert('ws_room_state', [
                'room_id' => $roomId,
                'state'   => $payload,
            ]);
        }
    }

    public function getRoomState(int $roomId): ?array
    {
        $row = $this->db->fetchOne('SELECT state FROM ws_room_state WHERE room_id = :r LIMIT 1', ['r' => $roomId]);
        if (!$row) return null;
        return json_decode($row['state'], true);
    }
}
