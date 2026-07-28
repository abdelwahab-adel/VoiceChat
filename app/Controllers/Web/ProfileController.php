<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User as UserModel;
use App\Models\Report;
use App\Services\AuthService;
use App\Services\UploadService;
use App\Services\NotificationService;

class ProfileController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly AuthService $auth,
        private readonly UploadService $upload,
        private readonly NotificationService $notif
    ) {
        parent::__construct($request, $response, $db);
    }

    public function show(string $username): void
    {
        $userModel = new UserModel($this->db);
        $user = $userModel->findByUsername($username);
        if (!$user || $user['status'] !== 'active') {
            $this->response->view('errors.404', [], 404);
            return;
        }
        $profile = $userModel->publicProfile($user);
        $stats = $userModel->stats((int) $user['id']);
        $me = $this->user();
        $isMe = $me && (int) $me['id'] === (int) $user['id'];
        $isFollowing = $me ? $userModel->isFollowing((int) $me['id'], (int) $user['id']) : false;
        $isFriend = false; // can be checked if needed

        $this->render('profile.show', [
            'profile'     => $profile,
            'userData'    => $user,
            'stats'       => $stats,
            'isMe'        => $isMe,
            'isFollowing' => $isFollowing,
            'isFriend'    => $isFriend,
            'title'       => $profile['display_name'] . ' (@' . $profile['username'] . ')',
        ]);
    }

    public function me(): void
    {
        $user = $this->user();
        $this->redirect(url('u/' . $user['username']));
    }

    public function update(): void
    {
        $data = $this->validate([
            'display_name' => 'string|max:100',
            'bio'          => 'string|max:500',
            'gender'       => 'in:male,female,other',
            'country'      => 'string|max:80',
            'city'         => 'string|max:80',
            'language'     => 'string|max:20',
            'social_links' => 'string',
        ]);

        $user = $this->user();
        $update = array_intersect_key($data, array_flip(['display_name','bio','gender','country','city','language']));
        if (!empty($data['social_links'])) {
            $update['social_links'] = json_encode(array_filter(array_map('trim', explode("\n", $data['social_links']))));
        }
        if (!empty($update)) {
            $this->db->update('users', $update, 'id = :id', ['id' => $user['id']]);
        }
        $this->withFlash('success', 'Profile updated.');
        $this->redirect(url('profile'));
    }

    public function avatar(): void
    {
        $file = $this->request->file('avatar');
        if (!$file) {
            $this->withFlash('error', 'No file uploaded.');
            $this->back();
            return;
        }
        try {
            $info = $this->upload->uploadForUser((int) $this->user()['id'], $file, 'avatars', 'avatar', [
                'max_width' => 800, 'max_height' => 800, 'quality' => 90,
            ]);
            $this->db->update('users', ['avatar' => $info['path']], 'id = :id', ['id' => $this->user()['id']]);
            $this->withFlash('success', 'Avatar updated.');
        } catch (\Throwable $e) {
            $this->withFlash('error', $e->getMessage());
        }
        $this->back();
    }

    public function cover(): void
    {
        $file = $this->request->file('cover');
        if (!$file) {
            $this->withFlash('error', 'No file uploaded.');
            $this->back();
            return;
        }
        try {
            $info = $this->upload->uploadForUser((int) $this->user()['id'], $file, 'covers', 'cover', [
                'max_width' => 1920, 'max_height' => 600, 'quality' => 85,
            ]);
            $this->db->update('users', ['cover' => $info['path']], 'id = :id', ['id' => $this->user()['id']]);
            $this->withFlash('success', 'Cover updated.');
        } catch (\Throwable $e) {
            $this->withFlash('error', $e->getMessage());
        }
        $this->back();
    }

    public function changePassword(): void
    {
        $data = $this->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|max:100',
        ]);
        try {
            $this->auth->changePassword((int) $this->user()['id'], $data['current_password'], $data['new_password']);
            $this->withFlash('success', 'Password changed. Please log in again.');
            $this->auth->logout();
            $this->redirect(url('login'));
        } catch (\Throwable $e) {
            $this->withFlash('error', $e->getMessage());
            $this->back();
        }
    }

    public function followers(string $userId): void
    {
        $userModel = new UserModel($this->db);
        $rows = $userModel->followers((int) $userId, 60, 0);
        $this->render('profile.followers', [
            'users' => $rows,
            'title' => 'Followers',
        ]);
    }

    public function following(string $userId): void
    {
        $userModel = new UserModel($this->db);
        $rows = $userModel->following((int) $userId, 60, 0);
        $this->render('profile.following', [
            'users' => $rows,
            'title' => 'Following',
        ]);
    }

    public function follow(string $userId): void
    {
        $me = $this->user();
        $userModel = new UserModel($this->db);
        if ($userModel->follow((int) $me['id'], (int) $userId)) {
            $this->notif->create((int) $userId, 'follow', 'New follower', ($me['display_name'] ?? $me['username']) . ' started following you', ['follower_id' => $me['id']], url('u/' . $me['username']));
        }
        $this->withFlash('success', 'Followed.');
        $this->back();
    }

    public function unfollow(string $userId): void
    {
        $me = $this->user();
        (new UserModel($this->db))->unfollow((int) $me['id'], (int) $userId);
        $this->withFlash('success', 'Unfollowed.');
        $this->back();
    }

    public function block(string $userId): void
    {
        $me = $this->user();
        (new UserModel($this->db))->block((int) $me['id'], (int) $userId);
        $this->withFlash('success', 'User blocked.');
        $this->back();
    }

    public function unblock(string $userId): void
    {
        $me = $this->user();
        (new UserModel($this->db))->unblock((int) $me['id'], (int) $userId);
        $this->withFlash('success', 'User unblocked.');
        $this->back();
    }

    public function report(string $type, string $id): void
    {
        $data = $this->validate([
            'reason'      => 'required|string|max:100',
            'description' => 'string|max:1000',
        ]);
        $me = $this->user();
        (new Report($this->db))->createReport((int) $me['id'], $type, (int) $id, $data['reason'], $data['description'] ?? null);
        $this->withFlash('success', 'Report submitted. Thank you.');
        $this->back();
    }
}
