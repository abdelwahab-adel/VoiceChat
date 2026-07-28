<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Room as RoomModel;
use App\Services\WebSocketService;

class RoomAdminController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly WebSocketService $ws
    ) {
        parent::__construct($request, $response, $db);
    }

    public function index(): void
    {
        $q = $this->request->get('q');
        $status = $this->request->get('status');
        $page = max(1, (int) $this->request->get('page', 1));
        $where = '1=1';
        $params = [];
        if ($q) { $where .= ' AND r.name LIKE :q'; $params['q'] = '%' . $q . '%'; }
        if ($status) { $where .= ' AND r.status = :s'; $params['s'] = $status; }
        $offset = max(0, ($page - 1) * 20);
        $total = (int) $this->db->fetchValue("SELECT COUNT(*) FROM rooms r WHERE {$where}", $params);
        $rooms = $this->db->fetchAll(
            "SELECT r.*, u.username as owner_username FROM rooms r JOIN users u ON u.id = r.owner_id WHERE {$where} ORDER BY r.id DESC LIMIT 20 OFFSET {$offset}",
            $params
        );
        $this->render('admin.rooms.index', [
            'rooms' => $rooms,
            'pager' => ['total' => $total, 'page' => $page, 'last_page' => max(1, (int) ceil($total / 20))],
            'q' => $q, 'status' => $status, 'title' => 'Manage Rooms',
        ]);
    }

    public function destroy(string $id): void
    {
        (new RoomModel($this->db, $this->ws))->update((int) $id, ['status' => 'banned']);
        $this->withFlash('success', 'Room banned.');
        $this->back();
    }

    public function feature(string $id): void
    {
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) return;
        (new RoomModel($this->db, $this->ws))->update((int) $id, ['is_featured' => $room['is_featured'] ? 0 : 1]);
        $this->withFlash('success', 'Room featured status toggled.');
        $this->back();
    }

    public function lock(string $id): void
    {
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) return;
        (new RoomModel($this->db, $this->ws))->update((int) $id, ['is_locked' => $room['is_locked'] ? 0 : 1]);
        $this->withFlash('success', 'Room lock status toggled.');
        $this->back();
    }
}
