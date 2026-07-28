<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Announcement;

class AnnouncementAdminController extends Controller
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
        $rows = $this->db->fetchAll("SELECT a.*, u.username as creator FROM announcements a JOIN users u ON u.id = a.created_by ORDER BY a.id DESC LIMIT 50");
        $this->render('admin.announcements.index', ['items' => $rows, 'title' => 'Announcements']);
    }

    public function create(): void
    {
        $this->render('admin.announcements.create', ['title' => 'New Announcement']);
    }

    public function store(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:200',
            'body'  => 'required|string',
            'type'  => 'in:info,warning,success,promo',
            'target' => 'in:all,users,vip,agency',
            'is_active' => 'integer',
            'starts_at' => 'string',
            'ends_at'   => 'string',
        ]);
        $data['is_active'] = !empty($data['is_active']) ? 1 : 0;
        (new Announcement($this->db))->createAnnouncement((int) $this->user()['id'], $data);
        $this->withFlash('success', 'Announcement created.');
        $this->redirect(url('admin/announcements'));
    }

    public function toggle(string $id): void
    {
        $a = $this->db->fetchOne('SELECT * FROM announcements WHERE id = :id', ['id' => $id]);
        if (!$a) return;
        $this->db->update('announcements', ['is_active' => $a['is_active'] ? 0 : 1], 'id = :id', ['id' => $id]);
        $this->withFlash('success', 'Toggled.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $this->db->delete('announcements', 'id = :id', ['id' => $id]);
        $this->withFlash('success', 'Deleted.');
        $this->back();
    }
}
