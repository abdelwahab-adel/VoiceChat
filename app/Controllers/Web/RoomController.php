<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Room as RoomModel;
use App\Models\Agency as AgencyModel;
use App\Services\WebSocketService;

class RoomController extends Controller
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
        $filters = [
            'category' => $this->request->get('category'),
            'type'     => $this->request->get('type'),
            'search'   => $this->request->get('q'),
        ];
        $page    = max(1, (int) $this->request->get('page', 1));
        $rooms   = (new RoomModel($this->db, $this->ws))->listRooms($filters, $page, 20);
        $this->render('rooms.index', [
            'rooms'   => $rooms['data'] ?? [],
            'pager'   => $rooms,
            'filters' => $filters,
            'title'   => 'Voice Rooms',
        ]);
    }

    public function create(): void
    {
        $agencies = (new AgencyModel($this->db))->listAgencies(1, 20);
        $this->render('rooms.create', [
            'agencies' => $agencies['data'] ?? [],
            'title'    => 'Create a Room',
        ]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'        => 'required|string|min:2|max:80',
            'description' => 'string|max:500',
            'type'        => 'in:public,private,password,agency',
            'password'    => 'string|min:3|max:50',
            'max_seats'   => 'integer|min:2|max:16',
            'category'    => 'string|max:60',
            'tags'        => 'string',
            'language'    => 'string|max:20',
            'country'     => 'string|max:80',
            'agency_id'   => 'integer',
        ]);

        $user = $this->user();
        try {
            $id = (new RoomModel($this->db, $this->ws))->createRoom((int) $user['id'], $data);
            $this->withFlash('success', 'Room created!');
            $this->redirect(url('rooms/' . $id));
        } catch (\Throwable $e) {
            $this->withFlash('error', $e->getMessage());
            $this->back();
        }
    }

    public function show(string $id): void
    {
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room || $room['status'] === 'banned') {
            $this->response->view('errors.404', [], 404);
            return;
        }
        $participants = (new RoomModel($this->db, $this->ws))->participants((int) $id, true);
        $messages     = (new RoomModel($this->db, $this->ws))->messages((int) $id, 50);
        $this->render('rooms.show', [
            'room'         => $room,
            'participants' => $participants,
            'messages'     => $messages,
            'title'        => $room['name'],
        ]);
    }

    public function edit(string $id): void
    {
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) { $this->response->view('errors.404', [], 404); return; }
        $user = $this->user();
        if ((int) $room['owner_id'] !== (int) $user['id'] && !$this->auth->isAdmin()) {
            $this->response->view('errors.403', [], 403);
            return;
        }
        $this->render('rooms.edit', ['room' => $room, 'title' => 'Edit Room']);
    }

    public function update(string $id): void
    {
        $data = $this->validate([
            'name'        => 'string|min:2|max:80',
            'description' => 'string|max:500',
            'category'    => 'string|max:60',
            'max_seats'   => 'integer|min:2|max:16',
        ]);
        $user = $this->user();
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) { $this->withFlash('error', 'Room not found'); $this->back(); return; }
        if ((int) $room['owner_id'] !== (int) $user['id'] && !$this->auth->isAdmin()) {
            $this->withFlash('error', 'Permission denied'); $this->back(); return;
        }
        (new RoomModel($this->db, $this->ws))->update((int) $id, $data);
        $this->withFlash('success', 'Room updated.');
        $this->redirect(url('rooms/' . $id));
    }

    public function destroy(string $id): void
    {
        $user = $this->user();
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) return;
        if ((int) $room['owner_id'] !== (int) $user['id'] && !$this->auth->isAdmin()) return;
        (new RoomModel($this->db, $this->ws))->update((int) $id, ['status' => 'closed', 'ended_at' => date('Y-m-d H:i:s')]);
        $this->withFlash('success', 'Room closed.');
        $this->redirect(url('rooms'));
    }

    public function end(string $id): void
    {
        $this->destroy($id);
    }

    public function lock(string $id): void
    {
        $user = $this->user();
        $room = (new RoomModel($this->db, $this->ws))->find((int) $id);
        if (!$room) return;
        if ((int) $room['owner_id'] !== (int) $user['id']) return;
        $newStatus = !$room['is_locked'];
        (new RoomModel($this->db, $this->ws))->update((int) $id, ['is_locked' => $newStatus ? 1 : 0]);
        $this->ws->emit((int) $id, 'room_locked', ['locked' => $newStatus]);
        $this->withFlash('success', $newStatus ? 'Room locked.' : 'Room unlocked.');
        $this->back();
    }
}
