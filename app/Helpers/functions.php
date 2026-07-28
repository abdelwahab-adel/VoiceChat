<?php

declare(strict_types=1);

if (!function_exists('e')) {
    /**
     * HTML-escape a value.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Services\CsrfService::getInstance()->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $t = csrf_token();
        return '<input type="hidden" name="_csrf" value="' . $t . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $base . '/public/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return \App\Services\UrlService::route($name, $params);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $app = \App\Core\Application::getInstance();
        $value = $app->getConfig($keys[0] ?? '', []);
        for ($i = 1; $i < count($keys); $i++) {
            $value = $value[$keys[$i]] ?? null;
            if ($value === null) return $default;
        }
        return $value ?? $default;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): void
    {
        echo '<pre style="background:#1e1e1e;color:#ddd;padding:1em;border-radius:8px;text-align:left">';
        foreach ($vars as $v) var_dump($v);
        echo '</pre>';
        exit;
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B','KB','MB','GB','TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) $bytes /= 1024;
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('format_duration')) {
    function format_duration(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $datetime): string
    {
        if (!$datetime) return '';
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 60) return $diff . 's ago';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j, Y', $time);
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\-_]+/u', '-', $text) ?? '';
        $text = preg_replace('/-+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}

if (!function_exists('random_string')) {
    function random_string(int $length = 32): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        if ($message) echo $message;
        exit;
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return \App\Services\AuthService::getInstance()->user();
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        $u = auth_user();
        return $u ? (int) $u['id'] : null;
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return auth_id() !== null;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $app = \App\Core\Application::getInstance();
        $session = $app->getService('session');
        $old = $session?->get('_old_input', []) ?? [];
        return $old[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $default = null): ?string
    {
        $app = \App\Core\Application::getInstance();
        $session = $app->getService('session');
        $flashes = $session?->getFlashes() ?? [];
        $msg = $flashes[$key] ?? null;
        return $msg ?? $default;
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        $root = \App\Core\Application::getInstance()->rootPath();
        return $root . '/app' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $root = \App\Core\Application::getInstance()->rootPath();
        return $root . '/storage' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        $root = \App\Core\Application::getInstance()->rootPath();
        return $root . '/public' . ($path ? '/' . ltrim($path, '/') : '');
    }
}
