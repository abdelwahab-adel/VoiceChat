<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Request abstraction.
 * 
 * Wraps superglobals and provides a clean, immutable-style API.
 */
final class Request
{
    private array $query;
    private array $post;
    private array $cookies;
    private array $files;
    private array $server;
    private array $headers;
    private string $method;
    private string $path;
    private string $uri;
    private string $ip;
    private string $userAgent;
    private ?array $jsonBody = null;

    public function __construct(
        array $query,
        array $post,
        array $cookies,
        array $files,
        array $server
    ) {
        $this->query    = $query;
        $this->post     = $post;
        $this->cookies  = $cookies;
        $this->files    = $files;
        $this->server   = $server;
        $this->method   = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $this->uri      = $server['REQUEST_URI'] ?? '/';
        $this->path     = parse_url($this->uri, PHP_URL_PATH) ?: '/';
        $this->ip       = $this->resolveIp($server);
        $this->userAgent= $server['HTTP_USER_AGENT'] ?? '';
        $this->headers  = $this->parseHeaders($server);
    }

    /**
     * Build a request from the current PHP environment.
     */
    public static function capture(): self
    {
        $body = file_get_contents('php://input') ?: '';
        $json = [];
        if ($body !== '' && str_contains($this->headers['content-type'] ?? '', 'application/json')) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) $json = $decoded;
        }
        $r = new self($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
        $r->jsonBody = $json ?: null;
        return $r;
    }

    public function method(): string  { return $this->method; }
    public function path(): string    { return $this->path; }
    public function uri(): string     { return $this->uri; }
    public function ip(): string      { return $this->ip; }
    public function userAgent(): string { return $this->userAgent; }

    public function isGet(): bool    { return $this->method === 'GET'; }
    public function isPost(): bool   { return $this->method === 'POST'; }
    public function isPut(): bool    { return $this->method === 'PUT'; }
    public function isPatch(): bool  { return $this->method === 'PATCH'; }
    public function isDelete(): bool { return $this->method === 'DELETE'; }
    public function isAjax(): bool   { return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'; }
    public function wantsJson(): bool
    {
        $accept = $this->headers['accept'] ?? '';
        return str_contains($accept, 'application/json') || $this->isAjax() || $this->jsonBody !== null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->jsonBody !== null && array_key_exists($key, $this->jsonBody)) {
            return $this->jsonBody[$key];
        }
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->jsonBody ?? []);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if (is_string($auth) && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public function param(string $name, mixed $default = null): mixed
    {
        return $this->query[$name] ?? $this->post[$name] ?? $this->jsonBody[$name] ?? $default;
    }

    private function resolveIp(array $server): string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $key) {
            if (!empty($server[$key])) {
                $ip = explode(',', (string) $server[$key])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }

    private function parseHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        if (isset($server['CONTENT_TYPE'])) $headers['content-type'] = $server['CONTENT_TYPE'];
        if (isset($server['CONTENT_LENGTH'])) $headers['content-length'] = $server['CONTENT_LENGTH'];
        return $headers;
    }
}
