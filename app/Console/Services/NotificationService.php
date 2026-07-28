<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

/**
 * Notification Service.
 * 
 * Creates and queries user notifications.
 * Real-time delivery is handled by the WebSocket service.
 */
final class NotificationService
{
    public const TYPES = [
        'friend_request', 'friend_accepted',
        'gift_received', 'gift_sent',
        'room_invite', 'room_kick', 'room_mute',
        'agency_invite', 'agency_join_approved', 'agency_join_rejected',
        'message', 'follow', 'mention',
        'level_up', 'achievement', 'badge',
        'announcement', 'system',
    ];

    public function __construct(private readonly Database $db) {}

    public function create(int $userId, string $type, string $title, ?string $body = null, array $data = [], ?string $actionUrl = null, ?string $image = null, ?string $icon = null): string
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new RuntimeException("Invalid notification type: $type");
        }
        return $this->db->insert('notifications', [
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'data'       => $data ? json_encode($data) : null,
            'icon'       => $icon,
            'image'      => $image,
            'action_url' => $actionUrl,
        ]);
    }

    public function markAsRead(int $userId, int $notificationId): bool
    {
        return $this->db->update(
            'notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'id = :id AND user_id = :u',
            ['id' => $notificationId, 'u' => $userId]
        ) > 0;
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->db->update(
            'notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'user_id = :u AND is_read = 0',
            ['u' => $userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        return (int) $this->db->fetchValue(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :u AND is_read = 0',
            ['u' => $userId]
        );
    }

    public function list(int $userId, int $limit = 30, int $offset = 0, bool $unreadOnly = false): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = :u';
        $params = ['u' => $userId];
        if ($unreadOnly) $sql .= ' AND is_read = 0';
        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function delete(int $userId, int $notificationId): bool
    {
        return $this->db->delete(
            'notifications',
            'id = :id AND user_id = :u',
            ['id' => $notificationId, 'u' => $userId]
        ) > 0;
    }
}
