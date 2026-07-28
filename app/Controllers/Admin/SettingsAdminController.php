<?php

declare(strict_types=1);

namespace App\Controllers\Admin;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Setting;

class SettingsAdminController extends Controller
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
        $rows = $this->db->fetchAll("SELECT * FROM settings ORDER BY group_name, key_name");
        $this->render('admin.settings.index', ['settings' => $rows, 'title' => 'Settings']);
    }

    public function update(): void
    {
        $data = $this->request->all();
        $model = new Setting($this->db);
        $count = 0;
        foreach ($data as $key => $value) {
            if (!str_starts_with($key, 'setting_')) continue;
            $name = substr($key, 8);
            $model->set($name, $value, 'string', 'general');
            $count++;
        }
        $this->withFlash('success', "$count settings updated.");
        $this->back();
    }
}
