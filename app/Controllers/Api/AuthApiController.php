<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\JwtService;

class AuthApiController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly AuthService $auth,
        private readonly JwtService $jwt
    ) {
        parent::__construct($request, $response, $db);
    }

    public function me(): void
    {
        if (!$this->auth->check()) {
            $this->json(['authenticated' => false], 401);
            return;
        }
        $this->success($this->auth->publicUser());
    }

    public function login(): void
    {
        $data = $this->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
            'device'   => 'string|max:255',
        ]);
        try {
            $result = $this->auth->login(
                $data['login'],
                $data['password'],
                $this->request->ip(),
                $this->request->userAgent(),
                $data['device'] ?? null
            );
            $this->success($result, 'Logged in');
        } catch (\Throwable $e) {
            $this->fail($e->getMessage(), [], $e->getCode() ?: 401);
        }
    }

    public function register(): void
    {
        $data = $this->validate([
            'username' => 'required|string|min:3|max:30|regex:[a-zA-Z0-9_]',
            'email'    => 'required|email',
            'password' => 'required|string|min:6|max:100',
            'display_name' => 'string|max:100',
            'gender'   => 'in:male,female,other',
            'country'  => 'string|max:80',
        ]);
        try {
            $user = $this->auth->register($data, $this->request->ip(), $this->request->userAgent());
            $result = $this->auth->login($data['email'], $data['password'], $this->request->ip(), $this->request->userAgent());
            $this->success(['user' => $user] + $result, 'Account created');
        } catch (\Throwable $e) {
            $this->fail($e->getMessage(), [], 422);
        }
    }

    public function refresh(): void
    {
        $data = $this->validate(['refresh_token' => 'required|string']);
        try {
            $tokens = $this->auth->refresh($data['refresh_token'], $this->request->ip(), $this->request->userAgent());
            $this->success($tokens, 'Refreshed');
        } catch (\Throwable $e) {
            $this->fail($e->getMessage(), [], 401);
        }
    }

    public function logout(): void
    {
        $token = $this->request->post('refresh_token') ?? $this->request->bearerToken();
        $this->auth->logout($token);
        $this->success(null, 'Logged out');
    }
}
