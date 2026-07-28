<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Friend as FriendModel;

class FriendController extends Controller
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
        $friendModel = new FriendModel($this->db, \App\Core\Application::getInstance()->getService('notif'));
        $accepted = $friendModel->list((int) $this->user()['id'], 'accepted');
        $pending  = $friendModel->list((int) $this->user()['id'], 'pending');
        $sent     = $friendModel->list((int) $this->user()['id'], 'sent');
        $this->render('friends.index', [
            'accepted' => $accepted,
            'pending'  => $pending,
            'sent'     => $sent,
            'title'    => 'Friends',
        ]);
    }

    public function sendRequest(string $userId): void
    {
        $me = (int) $this->user()['id'];
        (new FriendModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->sendRequest($me, (int) $userId);
        $this->withFlash('success', 'Friend request sent.');
        $this->back();
    }

    public function accept(string $userId): void
    {
        $me = (int) $this->user()['id'];
        (new FriendModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->accept($me, (int) $userId);
        $this->withFlash('success', 'Friend request accepted.');
        $this->back();
    }

    public function reject(string $userId): void
    {
        $me = (int) $this->user()['id'];
        (new FriendModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->reject($me, (int) $userId);
        $this->withFlash('info', 'Friend request rejected.');
        $this->back();
    }

    public function unfriend(string $userId): void
    {
        $me = (int) $this->user()['id'];
        (new FriendModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->unfriend($me, (int) $userId);
        $this->withFlash('info', 'Friend removed.');
        $this->back();
    }
}
