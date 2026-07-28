<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Agency as AgencyModel;

class AgencyAdminController extends Controller
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
        $page = max(1, (int) $this->request->get('page', 1));
        $where = '1=1';
        $params = [];
        if ($q) { $where .= ' AND a.name LIKE :q'; $params['q'] = '%' . $q . '%'; }
        $offset = max(0, ($page - 1) * 20);
        $total = (int) $this->db->fetchValue("SELECT COUNT(*) FROM agencies a WHERE {$where}", $params);
        $agencies = $this->db->fetchAll(
            "SELECT a.*, u.username as owner_username FROM agencies a JOIN users u ON u.id = a.owner_id WHERE {$where} ORDER BY a.id DESC LIMIT 20 OFFSET {$offset}",
            $params
        );
        $this->render('admin.agencies.index', [
            'agencies' => $agencies,
            'pager' => ['total' => $total, 'page' => $page, 'last_page' => max(1, (int) ceil($total / 20))],
            'q' => $q, 'title' => 'Manage Agencies',
        ]);
    }

    public function verify(string $id): void
    {
        $a = (new AgencyModel($this->db))->find((int) $id);
        if (!$a) return;
        (new AgencyModel($this->db))->update((int) $id, ['verified' => $a['verified'] ? 0 : 1]);
        $this->withFlash('success', 'Agency verification toggled.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        (new AgencyModel($this->db))->update((int) $id, ['status' => 'banned']);
        $this->withFlash('success', 'Agency banned.');
        $this->back();
    }
}
