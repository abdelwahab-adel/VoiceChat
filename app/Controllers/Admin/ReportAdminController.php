<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Report;

class ReportAdminController extends Controller
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
        $status = $this->request->get('status', 'pending');
        $page = max(1, (int) $this->request->get('page', 1));
        $result = (new Report($this->db))->list($page, 20, $status);
        $this->render('admin.reports.index', [
            'reports' => $result['data'],
            'pager'   => $result,
            'status'  => $status,
            'title'   => 'Reports',
        ]);
    }

    public function resolve(string $id): void
    {
        $this->db->update('reports', [
            'status' => 'resolved',
            'reviewed_by' => (int) $this->user()['id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);
        $this->withFlash('success', 'Report resolved.');
        $this->back();
    }

    public function dismiss(string $id): void
    {
        $this->db->update('reports', [
            'status' => 'dismissed',
            'reviewed_by' => (int) $this->user()['id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);
        $this->withFlash('success', 'Report dismissed.');
        $this->back();
    }
}
