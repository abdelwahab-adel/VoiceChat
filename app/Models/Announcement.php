<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Announcement extends Model
{
    protected string $table = 'announcements';
    protected array $fillable = [
        'title','body','image','type','target','is_active','starts_at','ends_at','created_by',
    ];
    protected array $casts = [
        'is_active' => 'bool',
    ];

    public function active(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM announcements
             WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY created_at DESC LIMIT 5"
        );
    }

    public function createAnnouncement(int $userId, array $data): string
    {
        $data['created_by'] = $userId;
        return $this->db->insert('announcements', $data);
    }
}
