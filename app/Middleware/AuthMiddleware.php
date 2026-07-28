<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Auth guard middleware.
 * 
 * Requires a logged-in user. Redirects web requests to /login
 * and returns 401 JSON for API requests.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly AuthService $auth
    ) {}

    public function handle(Request $request, Response $response): void
    {
        if (!$this->auth->check()) {
            if ($request->wantsJson() || str_starts_with($request->path(), '/api/')) {
                throw new \RuntimeException('Authentication required', 401);
            }
            $session = \App\Core\Application::getInstance()->getService('session');
            $session?->set('intended_url', $request->uri());
            $response->redirect(url('login'));
            exit;
        }
    }
}
