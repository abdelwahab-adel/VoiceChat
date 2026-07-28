<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'uuid','username','email','phone','password','display_name','bio','avatar','cover',
        'gender','birthdate','country','city','language','status','role','email_verified_at',
        'phone_verified_at','last_login_at','last_login_ip','online_status','last_seen_at',
        'is_verified','is_featured','settings','social_links','remember_token','coins','xp',
        'level','vip_level',
    ];
    protected array $hidden = ['password','remember_token'];
    protected array $casts = [
        'settings' => 'json',
        'social_links' => 'json',
        'is_verified' => 'bool',
        'is_featured' => 'bool',
        'coins' => 'int',
        'xp'    => 'int',
        'level' => 'int',
        'vip_level' => 'int',
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->findBy('username', $username);
    }

    public function findByLogin(string $login): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM users WHERE email = :l OR username = :l LIMIT 1',
            ['l' => $login]
        );
        return $row ? $this->hydrate($row) : null;
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->findBy('uuid', $uuid);
    }

    public function addXp(int $userId, int $amount, string $action = 'activity', ?string $reference = null): void
    {
        $row = $this->db->fetchOne('SELECT xp, level, coins FROM users WHERE id = :id', ['id' => $userId]);
        if (!$row) return;
        $newXp = (int) $row['xp'] + $amount;
        $newLevel = $this->computeLevel($newXp);
        $this->db->update('users', [
            'xp' => $newXp,
            'level' => $newLevel,
        ], 'id = :id', ['id' => $userId]);
        $this->db->insert('xp_history', [
            'user_id' => $userId, 'amount' => $amount, 'action' => $action, 'reference' => $reference,
        ]);
    }

    public function addCoins(int $userId, int $amount, string $type = 'reward', ?string $description = null, ?string $reference = null): int
    {
        $current = (int) $this->db->fetchValue('SELECT coins FROM users WHERE id = :id', ['id' => $userId]);
        $newBalance = max(0, $current + $amount);
        $this->db->update('users', ['coins' => $newBalance], 'id = :id', ['id' => $userId]);
        $this->db->insert('coin_transactions', [
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference' => $reference,
        ]);
        return $newBalance;
    }

    public function isFollowing(int $followerId, int $followingId): bool
    {
        return (bool) $this->db->fetchValue(
            'SELECT id FROM follows WHERE follower_id = :f AND following_id = :t LIMIT 1',
            ['f' => $followerId, 't' => $followingId]
        );
    }

    public function follow(int $followerId, int $followingId): bool
    {
        if ($followerId === $followingId) return false;
        if ($this->isFollowing($followerId, $followingId)) return false;
        $this->db->insert('follows', [
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]);
        return true;
    }

    public function unfollow(int $followerId, int $followingId): bool
    {
        $rows = $this->db->delete('follows', 'follower_id = :f AND following_id = :t', ['f' => $followerId, 't' => $followingId]);
        return $rows > 0;
    }

    public function followers(int $userId, int $limit = 30, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT u.id,u.uuid,u.username,u.display_name,u.avatar,u.online_status,u.is_verified,u.level
             FROM follows f JOIN users u ON u.id = f.follower_id
             WHERE f.following_id = :u ORDER BY f.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            ['u' => $userId]
        );
    }

    public function following(int $userId, int $limit = 30, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT u.id,u.uuid,u.username,u.display_name,u.avatar,u.online_status,u.is_verified,u.level
             FROM follows f JOIN users u ON u.id = f.following_id
             WHERE f.follower_id = :u ORDER BY f.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            ['u' => $userId]
        );
    }

    public function isBlocked(int $userId, int $byUserId): bool
    {
        return (bool) $this->db->fetchValue(
            'SELECT id FROM blocks WHERE user_id = :u AND blocked_id = :b LIMIT 1',
            ['u' => $byUserId, 'b' => $userId]
        );
    }

    public function block(int $userId, int $blockedId, ?string $reason = null): bool
    {
        if ($userId === $blockedId) return false;
        if ($this->isBlocked($userId, $blockedId)) return true;
        $this->db->insert('blocks', [
            'user_id' => $userId,
            'blocked_id' => $blockedId,
            'reason' => $reason,
        ]);
        return true;
    }

    public function unblock(int $userId, int $blockedId): bool
    {
        $rows = $this->db->delete('blocks', 'user_id = :u AND blocked_id = :b', ['u' => $userId, 'b' => $blockedId]);
        return $rows > 0;
    }

    public function publicProfile(array $user): array
    {
        return [
            'id'           => (int) $user['id'],
            'uuid'         => $user['uuid'],
            'username'     => $user['username'],
            'display_name' => $user['display_name'] ?? $user['username'],
            'bio'          => $user['bio'] ?? null,
            'avatar'       => !empty($user['avatar']) ? url('public/' . $user['avatar']) : null,
            'cover'        => !empty($user['cover'])  ? url('public/' . $user['cover'])  : null,
            'country'      => $user['country'] ?? null,
            'gender'       => $user['gender'] ?? null,
            'level'        => (int) ($user['level'] ?? 1),
            'vip_level'    => (int) ($user['vip_level'] ?? 0),
            'is_verified'  => (bool) ($user['is_verified'] ?? false),
            'is_featured'  => (bool) ($user['is_featured'] ?? false),
            'online_status'=> $user['online_status'] ?? 'offline',
            'last_seen_at' => $user['last_seen_at'] ?? null,
            'role'         => $user['role'] ?? 'user',
        ];
    }

    public function search(string $query, int $limit = 30): array
    {
        $like = '%' . str_replace(['%','_'], ['\%','\_'], $query) . '%';
        return $this->db->fetchAll(
            'SELECT id,uuid,username,display_name,avatar,is_verified,level,online_status
             FROM users
             WHERE status = "active" AND (username LIKE :q OR display_name LIKE :q)
             ORDER BY is_featured DESC, level DESC LIMIT ' . (int) $limit,
            ['q' => $like]
        );
    }

    public function computeLevel(int $xp): int
    {
        // 1000 xp per level, capped at 100
        return min(100, max(1, (int) floor($xp / 1000) + 1));
    }

    public function stats(int $userId): array
    {
        $followers  = (int) $this->db->fetchValue('SELECT COUNT(*) FROM follows WHERE following_id = :u', ['u' => $userId]);
        $following  = (int) $this->db->fetchValue('SELECT COUNT(*) FROM follows WHERE follower_id  = :u', ['u' => $userId]);
        $friends    = (int) $this->db->fetchValue('SELECT COUNT(*) FROM friends WHERE (user_id = :u OR friend_id = :u) AND status = "accepted"', ['u' => $userId]);
        $rooms      = (int) $this->db->fetchValue('SELECT COUNT(*) FROM rooms WHERE owner_id = :u', ['u' => $userId]);
        $giftsSent  = (int) $this->db->fetchValue('SELECT COALESCE(SUM(coins_total),0) FROM gift_transactions WHERE sender_id = :u', ['u' => $userId]);
        $giftsRecv  = (int) $this->db->fetchValue('SELECT COALESCE(SUM(coins_total),0) FROM gift_transactions WHERE receiver_id = :u', ['u' => $userId]);
        return [
            'followers' => $followers,
            'following' => $following,
            'friends'   => $friends,
            'rooms'     => $rooms,
            'gifts_sent'=> $giftsSent,
            'gifts_received' => $giftsRecv,
        ];
    }

    public function updateOnlineStatus(int $userId, string $status): void
    {
        $this->db->update('users', [
            'online_status' => $status,
            'last_seen_at'  => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $userId]);
    }
}
