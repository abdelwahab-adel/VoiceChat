<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;

class SettingsController extends Controller
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
        $user = $this->user();
        $settings = is_string($user['settings'] ?? null) ? json_decode((string) $user['settings'], true) : ($user['settings'] ?? []);
        $settings = is_array($settings) ? $settings : [];
        $this->render('settings.index', [
            'settings' => $settings,
            'userData' => $user,
            'title'    => 'Settings',
        ]);
    }

    public function update(): void
    {
        $data = $this->validate([
            'display_name' => 'string|max:100',
            'bio'          => 'string|max:500',
            'language'     => 'string|max:20',
            'country'      => 'string|max:80',
            'city'         => 'string|max:80',
            'gender'       => 'in:male,female,other',
        ]);
        $this->db->update('users', $data, 'id = :id', ['id' => $this->user()['id']]);
        $this->withFlash('success', 'Settings updated.');
        $this->back();
    }

    public function notifications(): void
    {
        $data = $this->validate([
            'notif_friend'     => 'integer',
            'notif_message'    => 'integer',
            'notif_gift'       => 'integer',
            'notif_room'       => 'integer',
            'notif_announce'   => 'integer',
        ]);
        $userId = (int) $this->user()['id'];
        $row = $this->db->fetchOne('SELECT settings FROM users WHERE id = :id', ['id' => $userId]);
        $settings = is_string($row['settings'] ?? null) ? json_decode((string) $row['settings'], true) : [];
        $settings = is_array($settings) ? $settings : [];
        $settings['notifications'] = array_map('intval', $data);
        $this->db->update('users', ['settings' => json_encode($settings)], 'id = :id', ['id' => $userId]);
        $this->withFlash('success', 'Notification preferences updated.');
        $this->back();
    }

    public function privacy(): void
    {
        $data = $this->validate([
            'show_online'    => 'integer',
            'allow_messages' => 'in:everyone,friends,nobody',
            'allow_invites'  => 'in:everyone,friends,nobody',
        ]);
        $userId = (int) $this->user()['id'];
        $row = $this->db->fetchOne('SELECT settings FROM users WHERE id = :id', ['id' => $userId]);
        $settings = is_string($row['settings'] ?? null) ? json_decode((string) $row['settings'], true) : [];
        $settings = is_array($settings) ? $settings : [];
        $settings['privacy'] = $data;
        $this->db->update('users', ['settings' => json_encode($settings)], 'id = :id', ['id' => $userId]);
        $this->withFlash('success', 'Privacy settings updated.');
        $this->back();
    }
}
