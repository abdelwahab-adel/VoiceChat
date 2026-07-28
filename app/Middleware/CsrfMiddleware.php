<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\CsrfService;

/**
 * CSRF protection for state-changing requests.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly CsrfService $csrf
    ) {}

    public function handle(Request $request, Response $response): void
    {
        if (in_array($request->method(), ['GET','HEAD','OPTIONS'], true)) return;
        if (str_starts_with($request->path(), '/api/')) return;
        $token = $request->post('_csrf') ?? $request->header('x-csrf-token');
        if (!$this->csrf->validate(is_string($token) ? $token : null)) {
            if ($request->wantsJson()) {
                $response->json(['error' => 'CSRF token mismatch'], 419);
                exit;
            }
            $session = \App\Core\Application::getInstance()->getService('session');
            $session?->flash('error', 'Security token expired. Please try again.');
            $response->redirect($request->header('referer', '/') ?: '/');
            exit;
        }
    }
}
