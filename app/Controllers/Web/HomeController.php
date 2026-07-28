<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Room;
use App\Models\Agency;
use App\Models\Announcement;

class HomeController extends Controller
{
    public function __construct(Request $request, Response $response, Database $db)
    {
        parent::__construct($request, $response, $db);
    }

    public function index(): void
    {
        $rooms = (new Room($this->db, \App\Core\Application::getInstance()->getService('ws')))
            ->listRooms(['featured' => true], 1, 12);
        $agencies = (new Agency($this->db))
            ->listAgencies(1, 8, ['verified' => true]);
        $announcements = (new Announcement($this->db))->active();
        $totalUsers  = (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $totalRooms  = (int) $this->db->fetchValue("SELECT COUNT(*) FROM rooms WHERE status = 'active'");
        $totalOnline = (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE online_status = 'online'");

        $this->render('home', [
            'rooms'        => $rooms['data'] ?? [],
            'agencies'     => $agencies['data'] ?? [],
            'announcements'=> $announcements,
            'stats'        => [
                'users'  => $totalUsers,
                'rooms'  => $totalRooms,
                'online' => $totalOnline,
            ],
            'title'        => 'VoiceChat — Connect Through Voice',
        ]);
    }

    public function explore(): void
    {
        $category = $this->request->get('category');
        $search   = $this->request->get('q');
        $rooms = (new Room($this->db, \App\Core\Application::getInstance()->getService('ws')))
            ->listRooms(array_filter([
                'category' => $category,
                'search'   => $search,
            ]), 1, 30);

        $this->render('explore', [
            'rooms'    => $rooms['data'] ?? [],
            'category' => $category,
            'search'   => $search,
            'title'    => 'Explore Voice Rooms',
        ]);
    }
}
