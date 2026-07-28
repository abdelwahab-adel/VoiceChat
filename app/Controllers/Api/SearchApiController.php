<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User as UserModel;
use App\Models\Room as RoomModel;
use App\Models\Agency as AgencyModel;
use App\Services\WebSocketService;

class SearchApiController extends Controller
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
        $q = trim((string) $this->request->get('q', ''));
        if ($q === '') { $this->success(['users' => [], 'rooms' => [], 'agencies' => []]); return; }
        $this->success([
            'users'    => (new UserModel($this->db))->search($q, 20),
            'rooms'    => (new RoomModel($this->db, $this->ws))->listRooms(['search' => $q], 1, 20)['data'] ?? [],
            'agencies' => (new AgencyModel($this->db))->listAgencies(1, 20, ['search' => $q])['data'] ?? [],
        ]);
    }
}
