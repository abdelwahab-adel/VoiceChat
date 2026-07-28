<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User as UserModel;

class UserAdminController extends Controller
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
        $q = $this->request->get('q');
        $status = $this->request->get('status');
        $page = max(1, (int) $this->request->get('page', 1));
        $where = '1=1';
        $params = [];
        if ($q) {
            $where .= ' AND (username LIKE :q OR email LIKE :q OR display_name LIKE :q OR id = :id)';
            $params['q'] = '%' . $q . '%';
            $params['id'] = is_numeric($q) ? (int) $q : 0;
        }
        if ($status) {
            $where .= ' AND status = :st';
            $params['st'] = $status;
        }
        $offset = max(0, ($page - 1) * 20);
        $total = (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE {$where}", $params);
        $users = $this->db->fetchAll("SELECT id, uuid, username, email, display_name, avatar, role, status, online_status, level, coins, xp, is_verified, created_at, last_login_at FROM users WHERE {$where} ORDER BY id DESC LIMIT 20 OFFSET {$offset}", $params);
        $this->render('admin.users.index', [
            'users' => $users,
            'pager' => ['total' => $total, 'page' => $page, 'last_page' => max(1, (int) ceil($total / 20))],
            'q' => $q, 'status' => $status,
            'title' => 'Manage Users',
        ]);
    }

    public function show(string $id): void
    {
        $user = (new UserModel($this->db))->find((int) $id);
        if (!$user) { $this->response->view('errors.404', [], 404); return; }
        $stats = (new UserModel($this->db))->stats((int) $id);
        $this->render('admin.users.show', [
            'userData' => $user, 'stats' => $stats, 'title' => 'User: ' . $user['username'],
        ]);
    }

    public function update(string $id): void
    {
        $data = $this->validate([
            'display_name' => 'string|max:100',
            'bio' => 'string|max:500',
            'role' => 'in:user,moderator,admin,superadmin',
            'status' => 'in:active,suspended,banned,pending',
            'is_verified' => 'integer',
            'is_featured' => 'integer',
        ]);
        $data['is_verified'] = !empty($data['is_verified']) ? 1 : 0;
        $data['is_featured'] = !empty($data['is_featured']) ? 1 : 0;
        (new UserModel($this->db))->update((int) $id, $data);
        $this->withFlash('success', 'User updated.');
        $this->back();
    }

    public function ban(string $id): void
    {
        $data = $this->validate([
            'reason' => 'required|string|max:500',
            'type'   => 'in:temporary,permanent',
            'days'   => 'integer|min:1|max:365',
        ]);
        $expires = ($data['type'] === 'permanent') ? null : date('Y-m-d H:i:s', time() + ((int) ($data['days'] ?? 7)) * 86400);
        $this->db->insert('bans', [
            'user_id'    => (int) $id,
            'banned_by'  => (int) $this->user()['id'],
            'reason'     => $data['reason'],
            'type'       => $data['type'],
            'expires_at' => $expires,
        ]);
        (new UserModel($this->db))->update((int) $id, ['status' => 'banned']);
        $this->withFlash('success', 'User banned.');
        $this->back();
    }

    public function unban(string $id): void
    {
        $this->db->update('bans', ['is_active' => 0], 'user_id = :u AND is_active = 1', ['u' => $id]);
        (new UserModel($this->db))->update((int) $id, ['status' => 'active']);
        $this->withFlash('success', 'User unbanned.');
        $this->back();
    }

    public function setRole(string $id): void
    {
        $data = $this->validate(['role' => 'required|in:user,moderator,admin,superadmin']);
        (new UserModel($this->db))->update((int) $id, ['role' => $data['role']]);
        $this->withFlash('success', 'Role updated.');
        $this->back();
    }

    public function addCoins(string $id): void
    {
        $data = $this->validate(['amount' => 'required|integer|min:1|max:1000000', 'note' => 'string|max:200']);
        (new UserModel($this->db))->addCoins((int) $id, (int) $data['amount'], 'admin_adjust', $data['note'] ?? 'Admin adjustment');
        $this->withFlash('success', 'Coins added.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        (new UserModel($this->db))->update((int) $id, ['status' => 'deleted']);
        $this->withFlash('success', 'User soft-deleted.');
        $this->back();
    }
}
