<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;

class LogAdminController extends Controller
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
        $files = glob(Application::getInstance()->rootPath() . '/storage/logs/*.log*') ?: [];
        $logs = [];
        foreach ($files as $f) {
            $logs[] = ['name' => basename($f), 'size' => filesize($f), 'mtime' => filemtime($f)];
        }
        $this->render('admin.logs.index', ['files' => $logs, 'title' => 'System Logs']);
    }

    public function activity(): void
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $offset = max(0, ($page - 1) * 30);
        $total = (int) $this->db->fetchValue('SELECT COUNT(*) FROM activity_logs');
        $rows = $this->db->fetchAll(
            'SELECT a.*, u.username FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT 30 OFFSET ' . $offset
        );
        $this->render('admin.logs.activity', [
            'rows'  => $rows,
            'pager' => ['total' => $total, 'page' => $page, 'last_page' => max(1, (int) ceil($total / 30))],
            'title' => 'Activity Logs',
        ]);
    }

    public function login(): void
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $offset = max(0, ($page - 1) * 30);
        $total = (int) $this->db->fetchValue('SELECT COUNT(*) FROM login_history');
        $rows = $this->db->fetchAll(
            'SELECT lh.*, u.username FROM login_history lh LEFT JOIN users u ON u.id = lh.user_id
             ORDER BY lh.id DESC LIMIT 30 OFFSET ' . $offset
        );
        $this->render('admin.logs.login', [
            'rows'  => $rows,
            'pager' => ['total' => $total, 'page' => $page, 'last_page' => max(1, (int) ceil($total / 30))],
            'title' => 'Login History',
        ]);
    }
}
