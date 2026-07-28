<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Gift as GiftModel;
use App\Services\WebSocketService;

class GiftApiController extends Controller
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
        $rows = (new GiftModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->active(100);
        $this->success($rows);
    }

    public function send(): void
    {
        $data = $this->validate([
            'gift_id'     => 'required|integer',
            'receiver_id' => 'required|integer',
            'quantity'    => 'integer|min:1|max:999',
            'room_id'     => 'integer',
            'agency_id'   => 'integer',
            'message'     => 'string|max:255',
            'anonymous'   => 'integer',
        ]);
        $user = $this->user();
        try {
            $result = (new GiftModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->send(
                (int) $data['gift_id'],
                (int) $user['id'],
                (int) $data['receiver_id'],
                (int) ($data['quantity'] ?? 1),
                $data['room_id'] ?? null,
                $data['agency_id'] ?? null,
                $data['message'] ?? null,
                !empty($data['anonymous'])
            );
            $this->success($result, 'Gift sent');
        } catch (\Throwable $e) {
            $this->fail($e->getMessage(), [], 422);
        }
    }

    public function history(): void
    {
        $user = $this->user();
        $direction = $this->request->get('direction', 'received');
        $rows = (new GiftModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->history((int) $user['id'], $direction, 50, 0);
        $this->success($rows);
    }
}
