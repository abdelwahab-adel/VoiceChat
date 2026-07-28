<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly NotificationService $notif
    ) {
        parent::__construct($request, $response, $db);
    }

    public function index(): void
    {
        $rows = $this->notif->list((int) $this->user()['id'], 50, 0);
        $this->render('notifications.index', [
            'notifications' => $rows,
            'title'         => 'Notifications',
        ]);
    }

    public function read(string $id): void
    {
        $this->notif->markAsRead((int) $this->user()['id'], (int) $id);
        $this->back();
    }

    public function readAll(): void
    {
        $this->notif->markAllAsRead((int) $this->user()['id']);
        $this->back();
    }
}
