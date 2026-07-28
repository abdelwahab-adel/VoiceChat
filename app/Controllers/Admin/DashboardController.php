<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
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
        $stats = [
            'users'      => (int) $this->db->fetchValue("SELECT COUNT(*) FROM users"),
            'online'     => (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE online_status = 'online'"),
            'rooms'      => (int) $this->db->fetchValue("SELECT COUNT(*) FROM rooms"),
            'active_rooms' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM rooms WHERE status = 'active'"),
            'agencies'   => (int) $this->db->fetchValue("SELECT COUNT(*) FROM agencies"),
            'gifts'      => (int) $this->db->fetchValue("SELECT COALESCE(SUM(coins_total),0) FROM gift_transactions"),
            'coins'      => (int) $this->db->fetchValue("SELECT COALESCE(SUM(coins),0) FROM users"),
            'messages'   => (int) $this->db->fetchValue("SELECT COUNT(*) FROM messages"),
            'reports'    => (int) $this->db->fetchValue("SELECT COUNT(*) FROM reports WHERE status = 'pending'"),
            'banned'     => (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE status = 'banned'"),
        ];

        $latestUsers   = $this->db->fetchAll("SELECT id, username, display_name, avatar, status, created_at FROM users ORDER BY id DESC LIMIT 10");
        $latestRooms   = $this->db->fetchAll("SELECT r.id, r.name, r.status, r.created_at, u.username as owner FROM rooms r JOIN users u ON u.id = r.owner_id ORDER BY r.id DESC LIMIT 10");
        $latestReports = $this->db->fetchAll("SELECT r.*, u.username as reporter FROM reports r JOIN users u ON u.id = r.reporter_id WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 10");

        $this->render('admin.dashboard', [
            'stats'         => $stats,
            'latestUsers'   => $latestUsers,
            'latestRooms'   => $latestRooms,
            'latestReports' => $latestReports,
            'title'         => 'Admin Dashboard',
        ]);
    }
}
