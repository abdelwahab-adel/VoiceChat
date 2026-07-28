<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Session service.
 * 
 * Wraps native PHP sessions with a clean API and flash messages.
 */
final class SessionService
{
    private bool $started = false;

    public function __construct(private array $config = [])
    {
        $this->configure();
        $this->start();
    }

    private function configure(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name($this->config['name'] ?? 'VC_SESSION');
        session_set_cookie_params([
            'lifetime' => (int) ($this->config['lifetime'] ?? 7200),
            'path'     => '/',
            'domain'   => '',
            'secure'   => (bool) ($this->config['secure'] ?? false),
            'httponly' => (bool) ($this->config['http_only'] ?? true),
            'samesite' => $this->config['same_site'] ?? 'Lax',
        ]);
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }
        if (headers_sent()) {
            throw new RuntimeException('Cannot start session: headers already sent');
        }
        @session_start();
        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION ?? []);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flush(): void
    {
        $_SESSION = [];
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        @session_destroy();
        $this->started = false;
    }

    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
        }
    }

    public function flash(string $type, string $message): void
    {
        $_SESSION['_flashes'][$type][] = $message;
    }

    public function getFlashes(): array
    {
        $flashes = $_SESSION['_flashes'] ?? [];
        unset($_SESSION['_flashes']);
        return $flashes;
    }

    public function token(): string
    {
        if (empty($_SESSION['_session_token'])) {
            $_SESSION['_session_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_session_token'];
    }

    public function id(): string
    {
        return session_id() ?: '';
    }
}
