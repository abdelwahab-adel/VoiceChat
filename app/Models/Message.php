<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\NotificationService;

class Message extends Model
{
    protected string $table = 'messages';
    protected array $fillable = [
        'sender_id','receiver_id','type','content','media_url','metadata','reply_to_id','is_read',
        'read_at','is_deleted_by_sender','is_deleted_by_receiver',
    ];
    protected array $casts = [
        'metadata' => 'json',
        'is_read'  => 'bool',
        'is_deleted_by_sender' => 'bool',
        'is_deleted_by_receiver' => 'bool',
    ];

    public function __construct(
        \App\Core\Database $db,
        private readonly NotificationService $notifications
    ) {
        parent::__construct($db);
    }

    public function send(int $senderId, int $receiverId, string $content, string $type = 'text', ?string $mediaUrl = null, array $metadata = [], ?int $replyToId = null): int
    {
        if ($senderId === $receiverId) {
            throw new \InvalidArgumentException('Cannot message yourself');
        }
        $id = $this->db->insert('messages', [
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'type'        => $type,
            'content'     => $content,
            'media_url'   => $mediaUrl,
            'metadata'    => $metadata ? json_encode($metadata) : null,
            'reply_to_id' => $replyToId,
        ]);
        $this->updateConversation($senderId, $receiverId, $id);
        $this->notifications->create(
            $receiverId,
            'message',
            'New message',
            mb_substr($content, 0, 100),
            ['sender_id' => $senderId, 'message_id' => $id],
            url('messages/' . $senderId)
        );
        return (int) $id;
    }

    public function updateConversation(int $userA, int $userB, int $messageId): void
    {
        [$one, $two] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];
        $row = $this->db->fetchOne('SELECT id FROM conversations WHERE user_one_id = :a AND user_two_id = :b LIMIT 1', ['a' => $one, 'b' => $two]);
        if ($row) {
            $col = ($one === $userB) ? 'user_one_unread' : 'user_two_unread';
            $this->db->query(
                "UPDATE conversations SET last_message_id = :m, updated_at = NOW(), {$col} = {$col} + 1 WHERE id = :id",
                ['m' => $messageId, 'id' => $row['id']]
            );
        } else {
            $this->db->insert('conversations', [
                'user_one_id'     => $one,
                'user_two_id'     => $two,
                'last_message_id' => $messageId,
                'user_one_unread' => $one === $userB ? 1 : 0,
                'user_two_unread' => $two === $userB ? 1 : 0,
            ]);
        }
    }

    public function conversation(int $userA, int $userB, int $limit = 50, ?int $beforeId = null): array
    {
        [$one, $two] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];
        $sql = 'SELECT m.*, s.username as sender_username, s.display_name as sender_display_name, s.avatar as sender_avatar
                FROM messages m
                JOIN users s ON s.id = m.sender_id
                WHERE ((m.sender_id = :a AND m.receiver_id = :b) OR (m.sender_id = :b AND m.receiver_id = :a))
                  AND m.is_deleted_by_sender = 0 AND m.is_deleted_by_receiver = 0';
        $params = ['a' => $one, 'b' => $two];
        if ($beforeId) {
            $sql .= ' AND m.id < :bid';
            $params['bid'] = $beforeId;
        }
        $sql .= ' ORDER BY m.id DESC LIMIT ' . (int) $limit;
        $rows = $this->db->fetchAll($sql, $params);
        return array_reverse($rows);
    }

    public function markRead(int $userId, int $otherId): int
    {
        return $this->db->update('messages',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'sender_id = :s AND receiver_id = :r AND is_read = 0',
            ['s' => $otherId, 'r' => $userId]
        );
    }

    public function markConversationRead(int $userA, int $userB): int
    {
        [$one, $two] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];
        $col = ($one === $userA) ? 'user_one_unread' : 'user_two_unread';
        $rows = $this->db->update('messages',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'sender_id = :s AND receiver_id = :r AND is_read = 0',
            ['s' => $two, 'r' => $one]
        );
        $this->db->query("UPDATE conversations SET {$col} = 0 WHERE user_one_id = :a AND user_two_id = :b", ['a' => $one, 'b' => $two]);
        return $rows;
    }

    public function inbox(int $userId, int $limit = 30, int $offset = 0): array
    {
        $sql = 'SELECT c.*,
                       u.id as partner_id, u.uuid as partner_uuid, u.username as partner_username,
                       u.display_name as partner_display_name, u.avatar as partner_avatar,
                       u.online_status as partner_online_status, u.last_seen_at as partner_last_seen_at,
                       u.is_verified as partner_is_verified,
                       m.content as last_message_content, m.type as last_message_type, m.created_at as last_message_at
                FROM conversations c
                JOIN users u ON u.id = IF(c.user_one_id = :u, c.user_two_id, c.user_one_id)
                LEFT JOIN messages m ON m.id = c.last_message_id
                WHERE (c.user_one_id = :u AND c.user_one_deleted_at IS NULL)
                   OR (c.user_two_id = :u AND c.user_two_deleted_at IS NULL)
                ORDER BY c.updated_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $rows = $this->db->fetchAll($sql, ['u' => $userId]);
        foreach ($rows as &$row) {
            $row['unread_count'] = $row['user_one_id'] == $userId ? (int) $row['user_one_unread'] : (int) $row['user_two_unread'];
        }
        return $rows;
    }

    public function totalUnread(int $userId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COALESCE(SUM(CASE WHEN user_one_id = :u THEN user_one_unread ELSE user_two_unread END), 0) as total
             FROM conversations
             WHERE user_one_id = :u OR user_two_id = :u',
            ['u' => $userId]
        );
        return (int) ($row['total'] ?? 0);
    }

    public function setTyping(int $userId, int $otherId, bool $typing): void
    {
        [$one, $two] = $userId < $otherId ? [$userId, $otherId] : [$otherId, $userId];
        $col = $one === $userId ? 'user_one_typing' : 'user_two_typing';
        $this->db->update('conversations', [$col => $typing ? 1 : 0], 'user_one_id = :a AND user_two_id = :b', ['a' => $one, 'b' => $two]);
    }
}
