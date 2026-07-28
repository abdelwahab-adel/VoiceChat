<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;

class LeaderboardController extends Controller
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
                "SELECT a.*, u.username as owner_username FROM agencies a
                 JOIN users u ON u.id = a.owner_id
                 WHERE a.status = 'active'
                 ORDER BY a.verified DESC, a.level DESC, a.xp DESC LIMIT 50"
            ),
            'rooms' => $this->db->fetchAll(
                "SELECT r.*, u.username as owner_username FROM rooms r
                 JOIN users u ON u.id = r.owner_id
                 WHERE r.status = 'active'
                 ORDER BY r.current_listeners DESC, r.total_time DESC LIMIT 50"
            ),
            default => $this->db->fetchAll(
                "SELECT id, uuid, username, display_name, avatar, level, vip_level, is_verified, is_featured, online_status,
                        (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count
                 FROM users u
                 WHERE status = 'active'
                 ORDER BY vip_level DESC, level DESC, xp DESC LIMIT 50"
            ),
        };
        $this->render('leaderboard.index', [
            'data'  => $data,
            'type'  => $type,
            'title' => 'Leaderboard',
        ]);
    }
}
