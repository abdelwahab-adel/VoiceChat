<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Achievement extends Model
{
    protected string $table = 'achievements';
    protected array $fillable = ['name','slug','description','icon','xp_reward','coins_reward','criteria','is_active'];
    protected array $casts = [
        'criteria' => 'json',
        'is_active' => 'bool',
        'xp_reward' => 'int',
        'coins_reward' => 'int',
    ];

    public function active(): array
    {
        return $this->db->fetchAll('SELECT * FROM achievements WHERE is_active = 1 ORDER BY id ASC');
    }

    public function forUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, ua.progress, ua.target, ua.is_completed, ua.completed_at
             FROM user_achievements ua
             JOIN achievements a ON a.id = ua.achievement_id
             WHERE ua.user_id = :u
             ORDER BY ua.is_completed ASC, ua.completed_at DESC',
            ['u' => $userId]
        );
    }

    public function progress(int $userId, int $achievementId, int $increment = 1): bool
    {
        $a = $this->find($achievementId);
        if (!$a) return false;
        $existing = $this->db->fetchOne('SELECT * FROM user_achievements WHERE user_id = :u AND achievement_id = :a LIMIT 1', ['u' => $userId, 'a' => $achievementId]);
        if ($existing) {
            if ($existing['is_completed']) return false;
            $newProgress = (int) $existing['progress'] + $increment;
            $completed = $newProgress >= (int) $existing['target'];
            $this->db->update('user_achievements', [
                'progress' => $newProgress,
                'is_completed' => $completed ? 1 : 0,
                'completed_at' => $completed ? date('Y-m-d H:i:s') : null,
            ], 'id = :id', ['id' => $existing['id']]);
            if ($completed) {
                $this->grantRewards($userId, $a);
            }
            return $completed;
        }
        $this->db->insert('user_achievements', [
            'user_id' => $userId,
            'achievement_id' => $achievementId,
            'progress' => $increment,
            'target' => 1,
        ]);
        if ($increment >= 1) {
            $this->grantRewards($userId, $a);
        }
        return false;
    }

    private function grantRewards(int $userId, array $achievement): void
    {
        if (!empty($achievement['xp_reward'])) {
            $this->db->query('UPDATE users SET xp = xp + :xp, level = LEAST(100, FLOOR((xp + :xp) / 1000) + 1) WHERE id = :id',
                ['xp' => (int) $achievement['xp_reward'], 'id' => $userId]);
        }
        if (!empty($achievement['coins_reward'])) {
            $this->db->query('UPDATE users SET coins = coins + :c WHERE id = :id', ['c' => (int) $achievement['coins_reward'], 'id' => $userId]);
        }
    }
}
