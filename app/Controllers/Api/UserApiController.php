<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User as UserModel;
use App\Models\Report;

class UserApiController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db
    ) {
        parent::__construct($request, $response, $db);
    }

    public function show(string $username): void
    {
        $user = (new UserModel($this->db))->findByUsername($username) ?? (new UserModel($this->db))->find((int) $username);
        if (!$user) { $this->json(['error' => 'User not found'], 404); return; }
        $profile = (new UserModel($this->db))->publicProfile($user);
        $profile['stats'] = (new UserModel($this->db))->stats((int) $user['id']);
        $this->success($profile);
    }

    public function follow(string $userId): void
    {
        $me = $this->user();
        if ((new UserModel($this->db))->follow((int) $me['id'], (int) $userId)) {
            $this->success(['following' => true]);
        } else {
            $this->fail('Already following', [], 400);
        }
    }

    public function unfollow(string $userId): void
    {
        $me = $this->user();
        (new UserModel($this->db))->unfollow((int) $me['id'], (int) $userId);
        $this->success(['following' => false]);
    }

    public function block(string $userId): void
    {
        $me = $this->user();
        (new UserModel($this->db))->block((int) $me['id'], (int) $userId);
        $this->success(['blocked' => true]);
    }

    public function unblock(string $userId): void
    {
        $me = $this->user();
        (new UserModel($this->db))->unblock((int) $me['id'], (int) $userId);
        $this->success(['blocked' => false]);
    }

    public function report(string $userId): void
    {
        $data = $this->validate([
            'reason'      => 'required|string|max:100',
            'description' => 'string|max:1000',
        ]);
        (new Report($this->db))->createReport((int) $this->user()['id'], 'user', (int) $userId, $data['reason'], $data['description'] ?? null);
        $this->success(null, 'Report submitted');
    }
}
