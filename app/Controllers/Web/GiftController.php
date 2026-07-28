<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Gift as GiftModel;

class GiftController extends Controller
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
        $gifts = (new GiftModel($this->db, \App\Core\Application::getInstance()->getService('notif')))->active(60);
        $this->render('gifts.index', [
            'gifts' => $gifts,
            'title' => 'Send a Gift',
        ]);
    }

    public function history(): void
    {
        $direction = $this->request->get('direction', 'received');
        $giftModel = new GiftModel($this->db, \App\Core\Application::getInstance()->getService('notif'));
        $rows = $giftModel->history((int) $this->user()['id'], $direction, 100, 0);
        $this->render('gifts.history', [
            'history'   => $rows,
            'direction' => $direction,
            'title'     => 'Gift History',
        ]);
    }

    public function leaderboard(): void
    {
        $giftModel = new GiftModel($this->db, \App\Core\Application::getInstance()->getService('notif'));
        $topSent     = $giftModel->top(20, 'sent');
        $topReceived = $giftModel->top(20, 'received');
        $this->render('gifts.leaderboard', [
            'topSent'     => $topSent,
            'topReceived' => $topReceived,
            'title'       => 'Gift Leaderboard',
        ]);
    }
}
