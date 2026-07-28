<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;

class LeaderboardApiController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db
    ) {
        parent::__construct($request, $response, $db);
    }

    public function index(): void
    {
        $type = $this->request->get('type', 'users');
        $data = match ($type) {
            'agencies' => $this->db->fetchAll(
                "SELECT a.id, a.uuid, a.name, a.slug, a.logo, a.verified, a.level, a.xp, a.members_count
                 FROM agencies a WHERE a.status = 'active'
                 ORDER BY a.verified DESC, a.level DESC, a.xp DESC LIMIT 50"
            ),
            'rooms' => $this->db->fetchAll(
                "SELECT id, uuid, name, slug, cover, current_listeners, max_seats, type
                 FROM rooms WHERE status = 'active'
                 ORDER BY current_listeners DESC, total_time DESC LIMIT 50"
            ),
            default => $this->db->fetchAll(
                "SELECT id, uuid, username, display_name, avatar, level, vip_level, is_verified, is_featured, online_status
                 FROM users WHERE status = 'active'
                 ORDER BY vip_level DESC, level DESC, xp DESC LIMIT 50"
            ),
        };
        $this->success(['type' => $type, 'data' => $data]);
    }
}
