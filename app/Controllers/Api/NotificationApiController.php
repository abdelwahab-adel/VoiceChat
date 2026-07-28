<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Services\NotificationService;

class NotificationApiController extends Controller
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
        $rows = $this->notif->list((int) $this->user()['id'], 50, 0, (bool) $this->request->get('unread'));
        $this->success([
            'data'           => $rows,
            'unread_count'   => $this->notif->unreadCount((int) $this->user()['id']),
        ]);
    }

    public function read(string $id): void
    {
        $this->notif->markAsRead((int) $this->user()['id'], (int) $id);
        $this->success(null, 'OK');
    }

    public function readAll(): void
    {
        $this->notif->markAllAsRead((int) $this->user()['id']);
        $this->success(null, 'OK');
    }
}
