<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Agency as AgencyModel;
use App\Models\Room as RoomModel;
use App\Services\WebSocketService;
use App\Services\UploadService;
use App\Services\NotificationService;

class AgencyController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly WebSocketService $ws,
        private readonly UploadService $upload,
        private readonly NotificationService $notif
    ) {
        parent::__construct($request, $response, $db);
    }

    public function index(): void
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $agencies = (new AgencyModel($this->db))->listAgencies($page, 20, [
            'search' => $this->request->get('q'),
            'verified' => $this->request->get('verified'),
        ]);
        $this->render('agencies.index', [
            'agencies' => $agencies['data'] ?? [],
            'pager'    => $agencies,
            'title'    => 'Agencies',
        ]);
    }

    public function create(): void
    {
        $this->render('agencies.create', ['title' => 'Create Agency']);
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'        => 'required|string|min:3|max:120',
            'description' => 'string|max:2000',
            'country'     => 'string|max:80',
        ]);

        $user = $this->user();
        $id = (new AgencyModel($this->db))->createAgency((int) $user['id'], $data);
        $this->withFlash('success', 'Agency created!');
        $this->redirect(url('agencies/' . $id));
    }

    public function show(string $slug): void
    {
        $agency = (new AgencyModel($this->db))->findBy('slug', $slug) ?? (new AgencyModel($this->db))->find((int) $slug);
        if (!$agency || $agency['status'] !== 'active') {
            $this->response->view('errors.404', [], 404);
            return;
        }
        $members = (new AgencyModel($this->db))->listMembers((int) $agency['id']);
        $rooms   = $this->db->fetchAll('SELECT * FROM rooms WHERE agency_id = :a AND status = "active" ORDER BY current_listeners DESC LIMIT 20', ['a' => $agency['id']]);
        $user = $this->user();
        $isMember = $user ? (new AgencyModel($this->db))->isMember((int) $agency['id'], (int) $user['id']) : false;
        $isOwner  = $user && (int) $agency['owner_id'] === (int) $user['id'];

        $this->render('agencies.show', [
            'agency'   => $agency,
            'members'  => $members,
            'rooms'    => $rooms,
            'isMember' => $isMember,
            'isOwner'  => $isOwner,
            'title'    => $agency['name'],
        ]);
    }

    public function edit(string $id): void
    {
        $agency = (new AgencyModel($this->db))->find((int) $id);
        if (!$agency) { $this->response->view('errors.404', [], 404); return; }
        $user = $this->user();
        if ((int) $agency['owner_id'] !== (int) $user['id'] && !$this->auth->isAdmin()) {
            $this->response->view('errors.403', [], 403);
            return;
        }
        $this->render('agencies.edit', ['agency' => $agency, 'title' => 'Edit Agency']);
    }

    public function update(string $id): void
    {
        $data = $this->validate([
            'name'        => 'string|min:3|max:120',
            'description' => 'string|max:2000',
            'country'     => 'string|max:80',
        ]);
        $user = $this->user();
        $agency = (new AgencyModel($this->db))->find((int) $id);
        if (!$agency) return;
        if ((int) $agency['owner_id'] !== (int) $user['id'] && !$this->auth->isAdmin()) return;
        (new AgencyModel($this->db))->update((int) $id, $data);
        $this->withFlash('success', 'Agency updated.');
        $this->redirect(url('agencies/' . $agency['slug']));
    }

    public function join(string $id): void
    {
        $user = $this->user();
        $agencyModel = new AgencyModel($this->db);
        if ($agencyModel->isMember((int) $id, (int) $user['id'])) {
            $this->withFlash('info', 'You are already a member.');
            $this->back();
            return;
        }
        $agencyModel->join((int) $id, (int) $user['id']);
        $owner = $agencyModel->find((int) $id);
        if ($owner) {
            $this->notif->create(
                (int) $owner['owner_id'],
                'agency_join_request',
                'New join request',
                ($user['display_name'] ?? $user['username']) . ' wants to join your agency',
                ['agency_id' => $id, 'user_id' => $user['id']],
                url('agencies/' . $owner['slug'])
            );
        }
        $this->withFlash('success', 'Join request submitted.');
        $this->back();
    }

    public function approveRequest(string $id): void
    {
        $user = $this->user();
        $req = $this->db->fetchOne('SELECT * FROM agency_join_requests WHERE id = :id LIMIT 1', ['id' => $id]);
        if (!$req) { $this->withFlash('error', 'Request not found.'); $this->back(); return; }
        $agency = (new AgencyModel($this->db))->find((int) $req['agency_id']);
        $role = (new AgencyModel($this->db))->memberRole((int) $req['agency_id'], (int) $user['id']);
        if (!$agency || ((int) $agency['owner_id'] !== (int) $user['id'] && !in_array($role, ['admin','moderator'], true) && !$this->auth->isAdmin())) {
            $this->withFlash('error', 'Permission denied.'); $this->back(); return;
        }
        (new AgencyModel($this->db))->approveJoin((int) $id, (int) $user['id']);
        $this->notif->create((int) $req['user_id'], 'agency_join_approved', 'Request approved', 'You have been accepted into the agency', ['agency_id' => $agency['id']], url('agencies/' . $agency['slug']));
        $this->withFlash('success', 'Approved.');
        $this->back();
    }

    public function rejectRequest(string $id): void
    {
        $user = $this->user();
        $req = $this->db->fetchOne('SELECT * FROM agency_join_requests WHERE id = :id LIMIT 1', ['id' => $id]);
        if (!$req) return;
        $agency = (new AgencyModel($this->db))->find((int) $req['agency_id']);
        $role = (new AgencyModel($this->db))->memberRole((int) $req['agency_id'], (int) $user['id']);
        if (!$agency || ((int) $agency['owner_id'] !== (int) $user['id'] && !in_array($role, ['admin','moderator'], true) && !$this->auth->isAdmin())) return;
        (new AgencyModel($this->db))->rejectJoin((int) $id, (int) $user['id']);
        $this->notif->create((int) $req['user_id'], 'agency_join_rejected', 'Request rejected', 'Your request to join the agency was rejected', ['agency_id' => $agency['id']]);
        $this->withFlash('success', 'Rejected.');
        $this->back();
    }

    public function members(string $id): void
    {
        $agency = (new AgencyModel($this->db))->find((int) $id);
        $members = (new AgencyModel($this->db))->listMembers((int) $id);
        $this->render('agencies.members', [
            'agency' => $agency,
            'members' => $members,
            'title' => 'Agency Members',
        ]);
    }
}
