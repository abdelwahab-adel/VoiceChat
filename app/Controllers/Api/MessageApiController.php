<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Message as MessageModel;

class MessageApiController extends Controller
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
        $rows = (new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->inbox((int) $this->user()['id'], 50, 0);
        $this->success($rows);
    }

    public function show(string $userId): void
    {
        $messages = (new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->conversation((int) $this->user()['id'], (int) $userId, 50);
        (new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->markConversationRead((int) $this->user()['id'], (int) $userId);
        $this->success($messages);
    }

    public function send(string $userId): void
    {
        $data = $this->validate([
            'content'  => 'required|string|max:2000',
            'type'     => 'in:text,image,voice,file',
            'media_url'=> 'string',
            'reply_to' => 'integer',
            'metadata' => 'string',
        ]);
        $metadata = !empty($data['metadata']) ? json_decode($data['metadata'], true) : [];
        $metadata = is_array($metadata) ? $metadata : [];
        $id = (new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->send(
            (int) $this->user()['id'],
            (int) $userId,
            $data['content'],
            $data['type'] ?? 'text',
            $data['media_url'] ?? null,
            $metadata,
            $data['reply_to'] ?? null
        );
        $this->success(['id' => $id], 'Sent');
    }

    public function read(string $userId): void
    {
        $n = (new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->markConversationRead((int) $this->user()['id'], (int) $userId);
        $this->success(['marked' => $n], 'OK');
    }

    public function typing(string $userId): void
    {
        (new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->setTyping((int) $this->user()['id'], (int) $userId, true);
        $this->success(null, 'OK');
    }
}
