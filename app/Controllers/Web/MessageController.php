<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Message as MessageModel;
use App\Models\User as UserModel;

class MessageController extends Controller
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
        $messageModel = new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif'));
        $inbox = $messageModel->inbox((int) $this->user()['id'], 50, 0);
        $this->render('messages.index', [
            'inbox' => $inbox,
            'title' => 'Messages',
        ]);
    }

    public function show(string $userId): void
    {
        $userModel = new UserModel($this->db);
        $other = $userModel->find((int) $userId);
        if (!$other) { $this->response->view('errors.404', [], 404); return; }

        $messageModel = new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif'));
        $messages = $messageModel->conversation((int) $this->user()['id'], (int) $other['id'], 50);
        $messageModel->markConversationRead((int) $this->user()['id'], (int) $other['id']);

        $this->render('messages.show', [
            'other'    => $other,
            'messages' => $messages,
            'title'    => 'Chat with ' . $other['display_name'],
        ]);
    }

    public function send(string $userId): void
    {
        $data = $this->validate([
            'content'  => 'required|string|max:2000',
            'type'     => 'in:text,image,voice,file',
            'reply_to' => 'integer',
        ]);
        $messageModel = new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif'));
        $id = $messageModel->send(
            (int) $this->user()['id'],
            (int) $userId,
            $data['content'],
            $data['type'] ?? 'text',
            null,
            [],
            $data['reply_to'] ?? null
        );
        if ($this->request->wantsJson()) {
            $this->success(['id' => $id], 'Sent');
            return;
        }
        $this->back();
    }

    public function typing(string $userId): void
    {
        $messageModel = new MessageModel($this->db, \App\Core\Application::getInstance()->getService('notif'));
        $messageModel->setTyping((int) $this->user()['id'], (int) $userId, true);
        $this->json(['ok' => true]);
    }
}
