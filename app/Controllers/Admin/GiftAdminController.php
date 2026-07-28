<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Services\UploadService;

class GiftAdminController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly UploadService $upload
    ) {
        parent::__construct($request, $response, $db);
    }

    public function index(): void
    {
        $gifts = $this->db->fetchAll("SELECT * FROM gifts ORDER BY sort_order ASC, id DESC");
        $this->render('admin.gifts.index', ['gifts' => $gifts, 'title' => 'Gifts']);
    }

    public function create(): void
    {
        $this->render('admin.gifts.create', ['title' => 'New Gift']);
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'        => 'required|string|max:80',
            'description' => 'string|max:255',
            'category'    => 'string|max:50',
            'price_coins' => 'required|integer|min:1',
            'rarity'      => 'in:common,rare,epic,legendary,mythic',
            'is_animated' => 'integer',
            'sort_order'  => 'integer',
        ]);
        $data['slug'] = \slugify($data['name']);
        $data['is_animated'] = !empty($data['is_animated']) ? 1 : 0;
        $data['is_active']   = 1;
        if ($file = $this->request->file('image')) {
            try {
                $info = $this->upload->uploadForUser((int) $this->user()['id'], $file, 'gifts', 'gift');
                $data['image'] = $info['path'];
            } catch (\Throwable $e) {
                $this->withFlash('error', $e->getMessage());
                $this->back(); return;
            }
        }
        $id = $this->db->insert('gifts', $data);
        $this->withFlash('success', 'Gift created.');
        $this->redirect(url('admin/gifts/' . $id . '/edit'));
    }

    public function edit(string $id): void
    {
        $gift = $this->db->fetchOne('SELECT * FROM gifts WHERE id = :id', ['id' => $id]);
        if (!$gift) { $this->response->view('errors.404', [], 404); return; }
        $this->render('admin.gifts.edit', ['gift' => $gift, 'title' => 'Edit Gift']);
    }

    public function update(string $id): void
    {
        $data = $this->validate([
            'name'        => 'string|max:80',
            'description' => 'string|max:255',
            'category'    => 'string|max:50',
            'price_coins' => 'integer|min:1',
            'rarity'      => 'in:common,rare,epic,legendary,mythic',
            'is_animated' => 'integer',
            'is_active'   => 'integer',
            'sort_order'  => 'integer',
        ]);
        $data['is_animated'] = !empty($data['is_animated']) ? 1 : 0;
        $data['is_active']   = !empty($data['is_active']) ? 1 : 0;
        if ($file = $this->request->file('image')) {
            try {
                $info = $this->upload->uploadForUser((int) $this->user()['id'], $file, 'gifts', 'gift');
                $data['image'] = $info['path'];
            } catch (\Throwable $e) {
                $this->withFlash('error', $e->getMessage());
            }
        }
        $this->db->update('gifts', $data, 'id = :id', ['id' => $id]);
        $this->withFlash('success', 'Gift updated.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $this->db->delete('gifts', 'id = :id', ['id' => $id]);
        $this->withFlash('success', 'Gift deleted.');
        $this->back();
    }
}
