<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Report extends Model
{
    protected string $table = 'reports';
    protected array $fillable = [
        'reporter_id','target_type','target_id','reason','description','status',
        'reviewed_by','reviewed_at',
    ];

    public function createReport(int $reporterId, string $type, int $targetId, string $reason, ?string $description = null): string
    {
        return $this->db->insert('reports', [
            'reporter_id' => $reporterId,
            'target_type' => $type,
            'target_id'   => $targetId,
            'reason'      => $reason,
            'description' => $description,
        ]);
    }

    public function list(int $page = 1, int $perPage = 20, ?string $status = null): array
    {
        $where = '1=1';
        $params = [];
        if ($status) {
            $where = 'status = :s';
            $params['s'] = $status;
        }
        $offset = max(0, ($page - 1) * $perPage);
        $total = (int) $this->db->fetchValue("SELECT COUNT(*) FROM reports WHERE {$where}", $params);
        $rows = $this->db->fetchAll(
            "SELECT r.*, u.username as reporter_username, u.display_name as reporter_display_name
             FROM reports r JOIN users u ON u.id = r.reporter_id
             WHERE {$where} ORDER BY r.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'last_page' => max(1, (int) ceil($total / $perPage))];
    }
}
