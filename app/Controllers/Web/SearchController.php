<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User as UserModel;
use App\Models\Room as RoomModel;
use App\Models\Agency as AgencyModel;
use App\Services\WebSocketService;

class SearchController extends Controller
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
        $results = ['users' => [], 'rooms' => [], 'agencies' => []];
        if ($q !== '') {
            $results['users']    = (new UserModel($this->db))->search($q, 20);
            $results['rooms']    = (new RoomModel($this->db, $this->ws))->listRooms(['search' => $q], 1, 20)['data'] ?? [];
            $results['agencies'] = (new AgencyModel($this->db))->listAgencies(1, 20, ['search' => $q])['data'] ?? [];
        }
        $this->render('search.index', [
            'q'       => $q,
            'results' => $results,
            'title'   => 'Search',
        ]);
    }

    public function byType(string $type): void
    {
        $q = trim((string) $this->request->get('q', ''));
        $page = max(1, (int) $this->request->get('page', 1));
        $results = [];
        $pager = null;
        if ($q === '') { $this->redirect(url('search')); return; }
        switch ($type) {
            case 'users':
                $results = (new UserModel($this->db))->search($q, 50);
                break;
            case 'rooms':
                $pager = (new RoomModel($this->db, $this->ws))->listRooms(['search' => $q], $page, 20);
                $results = $pager['data'] ?? [];
                break;
            case 'agencies':
                $pager = (new AgencyModel($this->db))->listAgencies($page, 20, ['search' => $q]);
                $results = $pager['data'] ?? [];
                break;
            default:
                $this->redirect(url('search'));
                return;
        }
        $this->render('search.by_type', [
            'q'       => $q,
            'type'    => $type,
            'results' => $results,
            'pager'   => $pager,
            'title'   => 'Search: ' . ucfirst($type),
        ]);
    }
}
