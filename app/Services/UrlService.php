<?php

declare(strict_types=1);

namespace App\Services;

/**
 * URL helper service.
 * 
 * Generates URLs from named routes and static paths.
 */
final class UrlService
{
    private static array $routes = [];

    public static function register(array $routes): void
    {
        self::$routes = $routes;
    }

    public static function route(string $name, array $params = []): string
    {
        $route = self::$routes[$name] ?? null;
        if (!$route) return '/';
        $path = $route;
        foreach ($params as $k => $v) {
            $path = str_replace('{' . $k . '}', (string) $v, $path);
        }
        return $path;
    }
}
