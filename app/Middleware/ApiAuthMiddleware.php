<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * API Auth middleware.
 * 
 * Validates JWT bearer token (Authorization header).
 */
final class ApiAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly AuthService $auth
    ) {}

    public function handle(Request $request, Response $response): void
    {
        $token = $request->bearerToken();
        if (!$token) {
            $response->json(['error' => 'Missing token'], 401);
            exit;
        }
        $app = \App\Core\Application::getInstance();
        $jwt = $app->getService('jwt');
        $claims = $jwt?->tryValidate($token);
        if (!$claims) {
            $response->json(['error' => 'Invalid or expired token'], 401);
            exit;
        }
        $userId = (int) ($claims['sub'] ?? 0);
        if ($userId <= 0) {
            $response->json(['error' => 'Invalid token payload'], 401);
            exit;
        }
        $user = $app->getDb()->fetchOne('SELECT * FROM users WHERE id = :id AND status = "active" LIMIT 1', ['id' => $userId]);
        if (!$user) {
            $response->json(['error' => 'User not found'], 401);
            exit;
        }
        // Make the user available in container
        $app->getService('auth')?->setUser($user);
    }
}
