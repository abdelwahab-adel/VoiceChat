<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Guest guard - blocks access to login/register pages when already logged in.
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly AuthService $auth
    ) {}

    public function handle(Request $request, Response $response): void
    {
        if ($this->auth->check()) {
            $response->redirect(url(''));
            exit;
        }
    }
}
