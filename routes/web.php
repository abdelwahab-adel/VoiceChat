<?php

declare(strict_types=1);

use App\Controllers\Web\HomeController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\ProfileController;
use App\Controllers\Web\RoomController as WebRoomController;
use App\Controllers\Web\AgencyController as WebAgencyController;
use App\Controllers\Web\GiftController as WebGiftController;
use App\Controllers\Web\MessageController as WebMessageController;
use App\Controllers\Web\NotificationController as WebNotificationController;
use App\Controllers\Web\SearchController;
use App\Controllers\Web\SettingsController;
use App\Controllers\Web\FriendController;
use App\Controllers\Web\LeaderboardController;
use App\Controllers\Web\UploadController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\UserAdminController;
use App\Controllers\Admin\RoomAdminController;
use App\Controllers\Admin\AgencyAdminController;
use App\Controllers\Admin\GiftAdminController;
use App\Controllers\Admin\ReportAdminController;
use App\Controllers\Admin\AnnouncementAdminController;
use App\Controllers\Admin\SettingsAdminController;
use App\Controllers\Admin\LogAdminController;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\RoomApiController;
use App\Controllers\Api\GiftApiController;
use App\Controllers\Api\MessageApiController;
use App\Controllers\Api\NotificationApiController;
use App\Controllers\Api\UserApiController;
use App\Controllers\Api\AgencyApiController;
use App\Controllers\Api\LeaderboardApiController;
use App\Controllers\Api\SearchApiController;

/**
 * Register web routes.
 */
return function ($router): void {

    // Public pages
    $router->get('/', [HomeController::class, 'index'], ['name' => 'home']);
    $router->get('/explore', [HomeController::class, 'explore'], ['name' => 'explore']);
    $router->get('/leaderboard', [LeaderboardController::class, 'index'], ['name' => 'leaderboard']);

    // Auth
    $router->group(['prefix' => '', 'middleware' => ['guest']], function ($r) {
        $r->get('/login', [AuthController::class, 'showLogin'], ['name' => 'login']);
        $r->get('/register', [AuthController::class, 'showRegister'], ['name' => 'register']);
        $r->get('/forgot-password', [AuthController::class, 'showForgot'], ['name' => 'forgot']);
        $r->get('/reset-password', [AuthController::class, 'showReset'], ['name' => 'reset']);
        $r->post('/login', [AuthController::class, 'login'], ['name' => 'login.post']);
        $r->post('/register', [AuthController::class, 'register'], ['name' => 'register.post']);
        $r->post('/forgot-password', [AuthController::class, 'forgot'], ['name' => 'forgot.post']);
        $r->post('/reset-password', [AuthController::class, 'reset'], ['name' => 'reset.post']);
    });
    $router->post('/logout', [AuthController::class, 'logout'], ['name' => 'logout', 'middleware' => ['auth','csrf']]);

    // Email verification
    $router->get('/verify-email', [AuthController::class, 'verifyEmail'], ['name' => 'verify.email']);
    $router->get('/verify', [AuthController::class, 'verifyAccount'], ['name' => 'verify.account']);

    // Profile
    $router->get('/u/{username}', [ProfileController::class, 'show'], ['name' => 'profile']);
    $router->get('/profile', [ProfileController::class, 'me'], ['name' => 'profile.me', 'middleware' => ['auth']]);
    $router->post('/profile/update', [ProfileController::class, 'update'], ['name' => 'profile.update', 'middleware' => ['auth','csrf']]);
    $router->post('/profile/avatar', [ProfileController::class, 'avatar'], ['name' => 'profile.avatar', 'middleware' => ['auth','csrf']]);
    $router->post('/profile/cover', [ProfileController::class, 'cover'], ['name' => 'profile.cover', 'middleware' => ['auth','csrf']]);
    $router->post('/profile/password', [ProfileController::class, 'changePassword'], ['name' => 'profile.password', 'middleware' => ['auth','csrf']]);
    $router->get('/profile/{user}/followers', [ProfileController::class, 'followers'], ['name' => 'profile.followers']);
    $router->get('/profile/{user}/following', [ProfileController::class, 'following'], ['name' => 'profile.following']);

    // Friends
    $router->get('/friends', [FriendController::class, 'index'], ['name' => 'friends', 'middleware' => ['auth']]);
    $router->post('/friends/request/{user}', [FriendController::class, 'sendRequest'], ['name' => 'friends.request', 'middleware' => ['auth','csrf']]);
    $router->post('/friends/accept/{user}', [FriendController::class, 'accept'], ['name' => 'friends.accept', 'middleware' => ['auth','csrf']]);
    $router->post('/friends/reject/{user}', [FriendController::class, 'reject'], ['name' => 'friends.reject', 'middleware' => ['auth','csrf']]);
    $router->post('/friends/unfriend/{user}', [FriendController::class, 'unfriend'], ['name' => 'friends.unfriend', 'middleware' => ['auth','csrf']]);

    // Follow / Block
    $router->post('/follow/{user}', [ProfileController::class, 'follow'], ['name' => 'follow', 'middleware' => ['auth','csrf']]);
    $router->post('/unfollow/{user}', [ProfileController::class, 'unfollow'], ['name' => 'unfollow', 'middleware' => ['auth','csrf']]);
    $router->post('/block/{user}', [ProfileController::class, 'block'], ['name' => 'block', 'middleware' => ['auth','csrf']]);
    $router->post('/unblock/{user}', [ProfileController::class, 'unblock'], ['name' => 'unblock', 'middleware' => ['auth','csrf']]);
    $router->post('/report/{type}/{id}', [ProfileController::class, 'report'], ['name' => 'report', 'middleware' => ['auth','csrf']]);

    // Rooms
    $router->get('/rooms', [WebRoomController::class, 'index'], ['name' => 'rooms']);
    $router->get('/rooms/create', [WebRoomController::class, 'create'], ['name' => 'rooms.create', 'middleware' => ['auth']]);
    $router->post('/rooms/create', [WebRoomController::class, 'store'], ['name' => 'rooms.store', 'middleware' => ['auth','csrf']]);
    $router->get('/rooms/{id}', [WebRoomController::class, 'show'], ['name' => 'rooms.show']);
    $router->get('/rooms/{id}/edit', [WebRoomController::class, 'edit'], ['name' => 'rooms.edit', 'middleware' => ['auth']]);
    $router->post('/rooms/{id}/update', [WebRoomController::class, 'update'], ['name' => 'rooms.update', 'middleware' => ['auth','csrf']]);
    $router->post('/rooms/{id}/delete', [WebRoomController::class, 'destroy'], ['name' => 'rooms.delete', 'middleware' => ['auth','csrf']]);
    $router->post('/rooms/{id}/end', [WebRoomController::class, 'end'], ['name' => 'rooms.end', 'middleware' => ['auth','csrf']]);
    $router->post('/rooms/{id}/lock', [WebRoomController::class, 'lock'], ['name' => 'rooms.lock', 'middleware' => ['auth','csrf']]);

    // Agencies
    $router->get('/agencies', [WebAgencyController::class, 'index'], ['name' => 'agencies']);
    $router->get('/agencies/create', [WebAgencyController::class, 'create'], ['name' => 'agencies.create', 'middleware' => ['auth']]);
    $router->post('/agencies/create', [WebAgencyController::class, 'store'], ['name' => 'agencies.store', 'middleware' => ['auth','csrf']]);
    $router->get('/agencies/{slug}', [WebAgencyController::class, 'show'], ['name' => 'agencies.show']);
    $router->get('/agencies/{id}/edit', [WebAgencyController::class, 'edit'], ['name' => 'agencies.edit', 'middleware' => ['auth']]);
    $router->post('/agencies/{id}/update', [WebAgencyController::class, 'update'], ['name' => 'agencies.update', 'middleware' => ['auth','csrf']]);
    $router->post('/agencies/{id}/join', [WebAgencyController::class, 'join'], ['name' => 'agencies.join', 'middleware' => ['auth','csrf']]);
    $router->post('/agencies/requests/{id}/approve', [WebAgencyController::class, 'approveRequest'], ['name' => 'agencies.approve', 'middleware' => ['auth','csrf']]);
    $router->post('/agencies/requests/{id}/reject', [WebAgencyController::class, 'rejectRequest'], ['name' => 'agencies.reject', 'middleware' => ['auth','csrf']]);
    $router->get('/agencies/{id}/members', [WebAgencyController::class, 'members'], ['name' => 'agencies.members']);

    // Gifts
    $router->get('/gifts', [WebGiftController::class, 'index'], ['name' => 'gifts', 'middleware' => ['auth']]);
    $router->get('/gifts/history', [WebGiftController::class, 'history'], ['name' => 'gifts.history', 'middleware' => ['auth']]);
    $router->get('/gifts/leaderboard', [WebGiftController::class, 'leaderboard'], ['name' => 'gifts.leaderboard']);

    // Messages
    $router->get('/messages', [WebMessageController::class, 'index'], ['name' => 'messages', 'middleware' => ['auth']]);
    $router->get('/messages/{user}', [WebMessageController::class, 'show'], ['name' => 'messages.show', 'middleware' => ['auth']]);
    $router->post('/messages/{user}/send', [WebMessageController::class, 'send'], ['name' => 'messages.send', 'middleware' => ['auth','csrf']]);
    $router->post('/messages/{user}/typing', [WebMessageController::class, 'typing'], ['name' => 'messages.typing', 'middleware' => ['auth','csrf']]);

    // Notifications
    $router->get('/notifications', [WebNotificationController::class, 'index'], ['name' => 'notifications', 'middleware' => ['auth']]);
    $router->post('/notifications/{id}/read', [WebNotificationController::class, 'read'], ['name' => 'notifications.read', 'middleware' => ['auth','csrf']]);
    $router->post('/notifications/read-all', [WebNotificationController::class, 'readAll'], ['name' => 'notifications.read.all', 'middleware' => ['auth','csrf']]);

    // Search
    $router->get('/search', [SearchController::class, 'index'], ['name' => 'search']);
    $router->get('/search/{type}', [SearchController::class, 'byType'], ['name' => 'search.type']);

    // Settings
    $router->get('/settings', [SettingsController::class, 'index'], ['name' => 'settings', 'middleware' => ['auth']]);
    $router->post('/settings/update', [SettingsController::class, 'update'], ['name' => 'settings.update', 'middleware' => ['auth','csrf']]);
    $router->post('/settings/notifications', [SettingsController::class, 'notifications'], ['name' => 'settings.notifications', 'middleware' => ['auth','csrf']]);
    $router->post('/settings/privacy', [SettingsController::class, 'privacy'], ['name' => 'settings.privacy', 'middleware' => ['auth','csrf']]);

    // Uploads (general purpose)
    $router->post('/upload/image', [UploadController::class, 'image'], ['name' => 'upload.image', 'middleware' => ['auth','csrf']]);
    $router->post('/upload/audio', [UploadController::class, 'audio'], ['name' => 'upload.audio', 'middleware' => ['auth','csrf']]);

    // API endpoints (called by JavaScript)
    $router->group(['prefix' => '/api'], function ($r) {
        $r->get('/me', [AuthApiController::class, 'me'], ['name' => 'api.me']);
        $r->post('/auth/login', [AuthApiController::class, 'login'], ['name' => 'api.login']);
        $r->post('/auth/register', [AuthApiController::class, 'register'], ['name' => 'api.register']);
        $r->post('/auth/refresh', [AuthApiController::class, 'refresh'], ['name' => 'api.refresh']);
        $r->post('/auth/logout', [AuthApiController::class, 'logout'], ['name' => 'api.logout', 'middleware' => ['api.auth']]);
        $r->get('/rooms', [RoomApiController::class, 'index'], ['name' => 'api.rooms']);
        $r->get('/rooms/{id}', [RoomApiController::class, 'show'], ['name' => 'api.rooms.show']);
        $r->post('/rooms/{id}/join', [RoomApiController::class, 'join'], ['name' => 'api.rooms.join', 'middleware' => ['api.auth']]);
        $r->post('/rooms/{id}/leave', [RoomApiController::class, 'leave'], ['name' => 'api.rooms.leave', 'middleware' => ['api.auth']]);
        $r->post('/rooms/{id}/seat', [RoomApiController::class, 'seat'], ['name' => 'api.rooms.seat', 'middleware' => ['api.auth']]);
        $r->post('/rooms/{id}/mic', [RoomApiController::class, 'mic'], ['name' => 'api.rooms.mic', 'middleware' => ['api.auth']]);
        $r->post('/rooms/{id}/hand', [RoomApiController::class, 'hand'], ['name' => 'api.rooms.hand', 'middleware' => ['api.auth']]);
        $r->post('/rooms/{id}/chat', [RoomApiController::class, 'chat'], ['name' => 'api.rooms.chat', 'middleware' => ['api.auth']]);
        $r->post('/rooms/{id}/signaling', [RoomApiController::class, 'signaling'], ['name' => 'api.rooms.signaling', 'middleware' => ['api.auth']]);
        $r->get('/rooms/{id}/messages', [RoomApiController::class, 'messages'], ['name' => 'api.rooms.messages']);
        $r->get('/rooms/{id}/participants', [RoomApiController::class, 'participants'], ['name' => 'api.rooms.participants']);

        $r->get('/gifts', [GiftApiController::class, 'index'], ['name' => 'api.gifts']);
        $r->post('/gifts/send', [GiftApiController::class, 'send'], ['name' => 'api.gifts.send', 'middleware' => ['api.auth']]);
        $r->get('/gifts/history', [GiftApiController::class, 'history'], ['name' => 'api.gifts.history', 'middleware' => ['api.auth']]);

        $r->get('/messages', [MessageApiController::class, 'index'], ['name' => 'api.messages', 'middleware' => ['api.auth']]);
        $r->get('/messages/{user}', [MessageApiController::class, 'show'], ['name' => 'api.messages.show', 'middleware' => ['api.auth']]);
        $r->post('/messages/{user}/send', [MessageApiController::class, 'send'], ['name' => 'api.messages.send', 'middleware' => ['api.auth']]);
        $r->post('/messages/{user}/read', [MessageApiController::class, 'read'], ['name' => 'api.messages.read', 'middleware' => ['api.auth']]);
        $r->post('/messages/{user}/typing', [MessageApiController::class, 'typing'], ['name' => 'api.messages.typing', 'middleware' => ['api.auth']]);

        $r->get('/notifications', [NotificationApiController::class, 'index'], ['name' => 'api.notifications', 'middleware' => ['api.auth']]);
        $r->post('/notifications/{id}/read', [NotificationApiController::class, 'read'], ['name' => 'api.notifications.read', 'middleware' => ['api.auth']]);
        $r->post('/notifications/read-all', [NotificationApiController::class, 'readAll'], ['name' => 'api.notifications.read.all', 'middleware' => ['api.auth']]);

        $r->get('/users/{username}', [UserApiController::class, 'show'], ['name' => 'api.users.show']);
        $r->post('/users/{user}/follow', [UserApiController::class, 'follow'], ['name' => 'api.users.follow', 'middleware' => ['api.auth']]);
        $r->post('/users/{user}/unfollow', [UserApiController::class, 'unfollow'], ['name' => 'api.users.unfollow', 'middleware' => ['api.auth']]);
        $r->post('/users/{user}/block', [UserApiController::class, 'block'], ['name' => 'api.users.block', 'middleware' => ['api.auth']]);
        $r->post('/users/{user}/unblock', [UserApiController::class, 'unblock'], ['name' => 'api.users.unblock', 'middleware' => ['api.auth']]);
        $r->post('/users/{user}/report', [UserApiController::class, 'report'], ['name' => 'api.users.report', 'middleware' => ['api.auth']]);

        $r->get('/agencies', [AgencyApiController::class, 'index'], ['name' => 'api.agencies']);
        $r->get('/agencies/{id}', [AgencyApiController::class, 'show'], ['name' => 'api.agencies.show']);
        $r->post('/agencies/{id}/join', [AgencyApiController::class, 'join'], ['name' => 'api.agencies.join', 'middleware' => ['api.auth']]);

        $r->get('/leaderboard', [LeaderboardApiController::class, 'index'], ['name' => 'api.leaderboard']);
        $r->get('/search', [SearchApiController::class, 'index'], ['name' => 'api.search']);
    });

    // Admin
    $router->group(['prefix' => '/admin', 'middleware' => ['admin']], function ($r) {
        $r->get('/', [DashboardController::class, 'index'], ['name' => 'admin.dashboard']);
        $r->get('/users', [UserAdminController::class, 'index'], ['name' => 'admin.users']);
        $r->get('/users/{id}', [UserAdminController::class, 'show'], ['name' => 'admin.users.show']);
        $r->post('/users/{id}/update', [UserAdminController::class, 'update'], ['name' => 'admin.users.update', 'middleware' => ['csrf']]);
        $r->post('/users/{id}/ban', [UserAdminController::class, 'ban'], ['name' => 'admin.users.ban', 'middleware' => ['csrf']]);
        $r->post('/users/{id}/unban', [UserAdminController::class, 'unban'], ['name' => 'admin.users.unban', 'middleware' => ['csrf']]);
        $r->post('/users/{id}/role', [UserAdminController::class, 'setRole'], ['name' => 'admin.users.role', 'middleware' => ['csrf']]);
        $r->post('/users/{id}/coins', [UserAdminController::class, 'addCoins'], ['name' => 'admin.users.coins', 'middleware' => ['csrf']]);
        $r->post('/users/{id}/delete', [UserAdminController::class, 'destroy'], ['name' => 'admin.users.delete', 'middleware' => ['csrf']]);

        $r->get('/rooms', [RoomAdminController::class, 'index'], ['name' => 'admin.rooms']);
        $r->post('/rooms/{id}/delete', [RoomAdminController::class, 'destroy'], ['name' => 'admin.rooms.delete', 'middleware' => ['csrf']]);
        $r->post('/rooms/{id}/feature', [RoomAdminController::class, 'feature'], ['name' => 'admin.rooms.feature', 'middleware' => ['csrf']]);
        $r->post('/rooms/{id}/lock', [RoomAdminController::class, 'lock'], ['name' => 'admin.rooms.lock', 'middleware' => ['csrf']]);

        $r->get('/agencies', [AgencyAdminController::class, 'index'], ['name' => 'admin.agencies']);
        $r->post('/agencies/{id}/verify', [AgencyAdminController::class, 'verify'], ['name' => 'admin.agencies.verify', 'middleware' => ['csrf']]);
        $r->post('/agencies/{id}/delete', [AgencyAdminController::class, 'destroy'], ['name' => 'admin.agencies.delete', 'middleware' => ['csrf']]);

        $r->get('/gifts', [GiftAdminController::class, 'index'], ['name' => 'admin.gifts']);
        $r->get('/gifts/create', [GiftAdminController::class, 'create'], ['name' => 'admin.gifts.create']);
        $r->post('/gifts/create', [GiftAdminController::class, 'store'], ['name' => 'admin.gifts.store', 'middleware' => ['csrf']]);
        $r->get('/gifts/{id}/edit', [GiftAdminController::class, 'edit'], ['name' => 'admin.gifts.edit']);
        $r->post('/gifts/{id}/update', [GiftAdminController::class, 'update'], ['name' => 'admin.gifts.update', 'middleware' => ['csrf']]);
        $r->post('/gifts/{id}/delete', [GiftAdminController::class, 'destroy'], ['name' => 'admin.gifts.delete', 'middleware' => ['csrf']]);

        $r->get('/reports', [ReportAdminController::class, 'index'], ['name' => 'admin.reports']);
        $r->post('/reports/{id}/resolve', [ReportAdminController::class, 'resolve'], ['name' => 'admin.reports.resolve', 'middleware' => ['csrf']]);
        $r->post('/reports/{id}/dismiss', [ReportAdminController::class, 'dismiss'], ['name' => 'admin.reports.dismiss', 'middleware' => ['csrf']]);

        $r->get('/announcements', [AnnouncementAdminController::class, 'index'], ['name' => 'admin.announcements']);
        $r->get('/announcements/create', [AnnouncementAdminController::class, 'create'], ['name' => 'admin.announcements.create']);
        $r->post('/announcements/create', [AnnouncementAdminController::class, 'store'], ['name' => 'admin.announcements.store', 'middleware' => ['csrf']]);
        $r->post('/announcements/{id}/toggle', [AnnouncementAdminController::class, 'toggle'], ['name' => 'admin.announcements.toggle', 'middleware' => ['csrf']]);
        $r->post('/announcements/{id}/delete', [AnnouncementAdminController::class, 'destroy'], ['name' => 'admin.announcements.delete', 'middleware' => ['csrf']]);

        $r->get('/settings', [SettingsAdminController::class, 'index'], ['name' => 'admin.settings']);
        $r->post('/settings/update', [SettingsAdminController::class, 'update'], ['name' => 'admin.settings.update', 'middleware' => ['csrf']]);

        $r->get('/logs', [LogAdminController::class, 'index'], ['name' => 'admin.logs']);
        $r->get('/logs/activity', [LogAdminController::class, 'activity'], ['name' => 'admin.logs.activity']);
        $r->get('/logs/login', [LogAdminController::class, 'login'], ['name' => 'admin.logs.login']);
    });
};