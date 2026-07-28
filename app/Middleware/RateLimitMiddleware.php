<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\CacheService;

/**
 * Simple IP-based rate limiter using the cache service.
 * 
 * Default: 60 requests per 60 seconds per IP.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly CacheService $cache
    ) {}

    public function handle(Request $request, Response $response): void
    {
        $key  = 'rl:' . $request->ip() . ':' . $request->path();
        $max  = (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 60);
        $win  = (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
        $hits = (int) $this->cache->get($key, 0) + 1;
        $this->cache->set($key, $hits, $win);
        if ($hits > $max) {
            $response->json(['error' => 'Too many requests. Please slow down.'], 429);
            exit;
        }
    }
}
