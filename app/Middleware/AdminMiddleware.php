<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Admin guard - requires admin/superadmin role.
 */
final class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly AuthService $auth
    ) {}

    public function handle(Request $request, Response $response): void
    {
        if (!$this->auth->check()) {
            $response->redirect(url('login'));
            exit;
        }
        if (!$this->auth->isAdmin()) {
            if ($request->wantsJson() || str_starts_with($request->path(), '/api/')) {
                throw new \RuntimeException('Admin access required', 403);
            }
            $response->view('errors.403', [], 403);
            exit;
        }
    }
}
