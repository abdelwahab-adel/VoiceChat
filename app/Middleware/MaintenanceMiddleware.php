<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Maintenance mode - blocks non-admin users.
 */
final class MaintenanceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly AuthService $auth
    ) {}

    public function handle(Request $request, Response $response): void
    {
        if (!filter_var($_ENV['MAINTENANCE_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN)) return;
        if ($this->auth->isAdmin()) return;
        if (str_contains($request->path(), '/maintenance')) return;
        $response->view('errors.maintenance', [], 503);
        exit;
    }
}
