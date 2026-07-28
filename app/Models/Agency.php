<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Ramsey\Uuid\Uuid;

class Agency extends Model
{
    protected string $table = 'agencies';
    protected array $fillable = [
        'uuid','name','slug','description','logo','cover','owner_id','country','level','xp',
        'members_count','rooms_count','status','verified','settings',
    ];
    protected array $casts = [
        'settings' => 'json',
        'verified' => 'bool',
        'level' => 'int',
        'xp' => 'int',
        'members_count' => 'int',
        'rooms_count' => 'int',
    ];

    public function createAgency(int $ownerId, array $data): string
    {
        $id = $this->create([
            'uuid'        => Uuid::uuid4()->toString(),
            'name'        => $data['name'],
            'slug'        => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'logo'        => $data['logo'] ?? null,
            'cover'       => $data['cover'] ?? null,
            'owner_id'    => $ownerId,
            'country'     => $data['country'] ?? null,
            'level'       => 1,
            'members_count' => 1,
        ]);
        $this->db->insert('agency_members', [
            'agency_id' => $id,
            'user_id'   => $ownerId,
            'role'      => 'owner',
        ]);
        return $id;
    }

    public function isMember(int $agencyId, int $userId): bool
    {
        return (bool) $this->db->fetchValue(
            'SELECT id FROM agency_members WHERE agency_id = :a AND user_id = :u AND status = "active" LIMIT 1',
            ['a' => $agencyId, 'u' => $userId]
        );
    }

    public function memberRole(int $agencyId, int $userId): ?string
    {
        $row = $this->db->fetchOne(
            'SELECT role FROM agency_members WHERE agency_id = :a AND user_id = :u AND status = "active" LIMIT 1',
            ['a' => $agencyId, 'u' => $userId]
        );
        return $row ? (string) $row['role'] : null;
    }

    public function join(int $agencyId, int $userId, ?string $message = null): string
    {
        $existing = $this->db->fetchOne(
            'SELECT id, status FROM agency_join_requests WHERE agency_id = :a AND user_id = :u AND status = "pending" LIMIT 1',
            ['a' => $agencyId, 'u' => $userId]
        );
        if ($existing) return (string) $existing['id'];
        return $this->db->insert('agency_join_requests', [
            'agency_id' => $agencyId,
            'user_id'   => $userId,
            'message'   => $message,
        ]);
    }

    public function approveJoin(int $requestId, int $reviewerId): bool
    {
        $req = $this->db->fetchOne('SELECT * FROM agency_join_requests WHERE id = :id LIMIT 1', ['id' => $requestId]);
        if (!$req || $req['status'] !== 'pending') return false;
        $this->db->transaction(function ($db) use ($req, $reviewerId) {
            $db->update('agency_join_requests', [
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $req['id']]);
            $existing = $db->fetchOne('SELECT id FROM agency_members WHERE agency_id = :a AND user_id = :u LIMIT 1', ['a' => $req['agency_id'], 'u' => $req['user_id']]);
            if ($existing) {
                $db->update('agency_members', ['status' => 'active', 'left_at' => null], 'id = :id', ['id' => $existing['id']]);
            } else {
                $db->insert('agency_members', [
                    'agency_id' => $req['agency_id'],
                    'user_id'   => $req['user_id'],
                    'role'      => 'member',
                ]);
            }
            $db->query('UPDATE agencies SET members_count = members_count + 1 WHERE id = :a', ['a' => $req['agency_id']]);
        });
        return true;
    }

    public function rejectJoin(int $requestId, int $reviewerId): bool
    {
        return $this->db->update('agency_join_requests', [
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'id = :id AND status = "pending"', ['id' => $requestId]) > 0;
    }

    public function listMembers(int $agencyId, ?string $role = null): array
    {
        $sql = 'SELECT am.*, u.username, u.display_name, u.avatar, u.level, u.is_verified, u.online_status
                FROM agency_members am
                JOIN users u ON u.id = am.user_id
                WHERE am.agency_id = :a AND am.status = "active"';
        $params = ['a' => $agencyId];
        if ($role) {
            $sql .= ' AND am.role = :r';
            $params['r'] = $role;
        }
        $sql .= ' ORDER BY FIELD(am.role, "owner","admin","moderator","member"), am.joined_at ASC';
        return $this->db->fetchAll($sql, $params);
    }

    public function listAgencies(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(a.name LIKE :s OR a.description LIKE :s)';
            $params['s'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['country'])) {
            $where[] = 'a.country = :c';
            $params['c'] = $filters['country'];
        }
        if (!empty($filters['verified'])) {
            $where[] = 'a.verified = 1';
        }
        $where[] = 'a.status = "active"';
        $whereSql = implode(' AND ', $where);
        $offset = max(0, ($page - 1) * $perPage);
        $total = (int) $this->db->fetchValue("SELECT COUNT(*) FROM agencies a WHERE {$whereSql}", $params);
        $rows = $this->db->fetchAll(
            "SELECT a.*, u.username as owner_username, u.display_name as owner_display_name, u.avatar as owner_avatar
             FROM agencies a JOIN users u ON u.id = a.owner_id
             WHERE {$whereSql}
             ORDER BY a.verified DESC, a.level DESC, a.xp DESC
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

    public function uniqueSlug(string $name): string
    {
        $base = \slugify($name) ?: 'agency';
        $slug = $base;
        $i = 1;
        while ($this->findBy('slug', $slug)) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public function ranking(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, u.username as owner_username, u.display_name as owner_display_name
             FROM agencies a JOIN users u ON u.id = a.owner_id
             WHERE a.status = 'active'
             ORDER BY a.verified DESC, a.level DESC, a.xp DESC
             LIMIT " . (int) $limit
        );
    }
}
