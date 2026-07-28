<?php

declare(strict_types=1);

/**
 * Front controller - the only PHP entry point for HTTP requests.
 */

use App\Core\Application;
use App\Services\JwtService;
use App\Services\AuthService;
use App\Services\CsrfService;
use App\Services\UploadService;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\WebSocketService;
use App\Services\CacheService;
use App\Services\SessionService;
use App\Services\LoggerService;
use App\Models\User;
use App\Models\Room;
use App\Models\Agency;
use App\Models\Gift;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Follow;
use App\Models\Friend;
use App\Models\Report;
use App\Models\Badge;
use App\Models\Achievement;
use App\Models\Announcement;
use App\Models\Setting;

require_once __DIR__ . '/../vendor/autoload.php';

$app = Application::bootstrap(dirname(__DIR__));

// Bind shared services in the application container
$db    = $app->getDb();
$cfg   = $app->getConfig('jwt', []);
$jwt   = new JwtService($db, $cfg);
$req   = $app->getRequest();
$cache = $app->getService('cache');
$session = $app->getService('session');
$logger = $app->getService('logger');

$auth         = new AuthService($db, $jwt, $req);
$csrf         = new CsrfService($req);
$notif        = new NotificationService($db);
$ws           = new WebSocketService($db);
$mail         = new MailService($app->getConfig('mail', []));
$upload       = new UploadService($db, $app->getConfig('uploads', []));

// Expose services to controllers via setService()
$app->setService('auth',   $auth);
$app->setService('jwt',    $jwt);
$app->setService('csrf',   $csrf);
$app->setService('notif',  $notif);
$app->setService('ws',     $ws);
$app->setService('mail',   $mail);
$app->setService('upload', $upload);

// Register named routes for the URL service
\App\Services\UrlService::register([
    'home'                 => '/',
    'login'                => '/login',
    'register'             => '/register',
    'logout'               => '/logout',
    'forgot'               => '/forgot-password',
    'reset'                => '/reset-password',
    'profile'              => '/u/{username}',
    'profile.me'           => '/profile',
    'rooms'                => '/rooms',
    'rooms.create'         => '/rooms/create',
    'rooms.show'           => '/rooms/{id}',
    'agencies'             => '/agencies',
    'agencies.create'      => '/agencies/create',
    'agencies.show'        => '/agencies/{slug}',
    'gifts'                => '/gifts',
    'messages'             => '/messages',
    'messages.show'        => '/messages/{user}',
    'notifications'        => '/notifications',
    'friends'              => '/friends',
    'settings'             => '/settings',
    'search'               => '/search',
    'admin.dashboard'      => '/admin',
    'admin.users'          => '/admin/users',
    'admin.rooms'          => '/admin/rooms',
    'admin.agencies'       => '/admin/agencies',
    'admin.gifts'          => '/admin/gifts',
    'admin.reports'        => '/admin/reports',
    'admin.announcements'  => '/admin/announcements',
    'admin.settings'       => '/admin/settings',
    'admin.logs'           => '/admin/logs',
]);

// Bind model services for property-injection style controllers
\App\Services\UrlService::register([\App\Services\UrlService::class => 'noop']); // noop

// Load and register routes
$router = $app->getRouter();
$registerRoutes = require dirname(__DIR__) . '/routes/web.php';
$registerRoutes($router);

$app->run();
