<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\NotificationService;

class Friend extends Model
{
    protected string $table = 'friends';
    protected array $fillable = ['user_id','friend_id','status','accepted_at'];
    protected array $casts = [];

    public function __construct(
        \App\Core\Database $db,
        private readonly NotificationService $notifications
    ) {
        parent::__construct($db);
    }

    public function sendRequest(int $userId, int $friendId): bool
    {
        if ($userId === $friendId) throw new \InvalidArgumentException('Cannot add yourself');
        $row = $this->db->fetchOne(
            'SELECT * FROM friends WHERE (user_id = :a AND friend_id = :b) OR (user_id = :b AND friend_id = :a) LIMIT 1',
            ['a' => $userId, 'b' => $friendId]
        );
        if ($row) {
            if ($row['status'] === 'pending') return false;
            if ($row['status'] === 'accepted') return false;
            $this->db->update('friends', ['status' => 'pending'], 'id = :id', ['id' => $row['id']]);
        } else {
            $this->db->insert('friends', [
                'user_id'   => $userId,
                'friend_id' => $friendId,
                'status'    => 'pending',
            ]);
        }
        $this->notifications->create($friendId, 'friend_request', 'New friend request', 'You have a new friend request', ['from' => $userId], url('friends'));
        return true;
    }

    public function accept(int $userId, int $friendId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM friends WHERE user_id = :f AND friend_id = :u AND status = "pending" LIMIT 1',
            ['f' => $friendId, 'u' => $userId]
        );
        if (!$row) return false;
        $this->db->update('friends', [
            'status' => 'accepted',
            'accepted_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $row['id']]);
        $this->notifications->create($friendId, 'friend_accepted', 'Friend request accepted', 'You are now friends!', ['by' => $userId]);
        return true;
    }

    public function reject(int $userId, int $friendId): bool
    {
        return $this->db->delete('friends', 'user_id = :f AND friend_id = :u AND status = "pending"', ['f' => $friendId, 'u' => $userId]) > 0;
    }

    public function unfriend(int $userId, int $friendId): bool
    {
        $rows = $this->db->delete('friends', '(user_id = :a AND friend_id = :b) OR (user_id = :b AND friend_id = :a)', ['a' => $userId, 'b' => $friendId]);
        return $rows > 0;
    }

    public function isFriend(int $userId, int $friendId): bool
    {
        return (bool) $this->db->fetchValue(
            'SELECT id FROM friends WHERE ((user_id = :a AND friend_id = :b) OR (user_id = :b AND friend_id = :a)) AND status = "accepted" LIMIT 1',
            ['a' => $userId, 'b' => $friendId]
        );
    }

    public function list(int $userId, string $type = 'accepted'): array
    {
        $where = match ($type) {
            'pending' => '((f.user_id = :u AND f.status = "pending") OR (f.friend_id = :u AND f.status = "pending"))',
            'sent'    => '(f.user_id = :u AND f.status = "pending")',
            default   => '((f.user_id = :u OR f.friend_id = :u) AND f.status = "accepted")',
        };
        return $this->db->fetchAll(
            "SELECT f.*, u.id as user_id, u.username, u.display_name, u.avatar, u.online_status, u.level, u.is_verified
             FROM friends f JOIN users u ON u.id = IF(f.user_id = :u, f.friend_id, f.user_id)
             WHERE {$where}
             ORDER BY f.accepted_at DESC, f.created_at DESC",
            ['u' => $userId]
        );
    }
}
