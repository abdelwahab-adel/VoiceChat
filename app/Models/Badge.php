<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Badge extends Model
{
    protected string $table = 'badges';
    protected array $fillable = ['name','slug','description','icon','xp_required','type','rarity'];

    public function forUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT b.*, ub.awarded_at FROM user_badges ub
             JOIN badges b ON b.id = ub.badge_id
             WHERE ub.user_id = :u ORDER BY ub.awarded_at DESC',
            ['u' => $userId]
        );
    }

    public function award(int $userId, int $badgeId): bool
    {
        $exists = (bool) $this->db->fetchValue('SELECT id FROM user_badges WHERE user_id = :u AND badge_id = :b LIMIT 1', ['u' => $userId, 'b' => $badgeId]);
        if ($exists) return false;
        $this->db->insert('user_badges', ['user_id' => $userId, 'badge_id' => $badgeId]);
        return true;
    }
}
