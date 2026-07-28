<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use RuntimeException;

/**
 * CSRF Protection service.
 * 
 * Implements synchronizer token pattern with session storage.
 */
final class CsrfService
{
    private static ?self $instance = null;
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Request $request)
    {
        self::$instance = $this;
        $this->ensureSession();
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            throw new RuntimeException('CsrfService not initialized');
        }
        return self::$instance;
    }

    private function ensureSession(): void
    {
        $session = \App\Core\Application::getInstance()->getService('session');
        if (!$session) return;
        if (!$session->has(self::SESSION_KEY)) {
            $session->set(self::SESSION_KEY, bin2hex(random_bytes(32)));
        }
    }

    public function token(): string
    {
        $session = \App\Core\Application::getInstance()->getService('session');
        return $session?->get(self::SESSION_KEY) ?? '';
    }

    public function validate(?string $token): bool
    {
        if (!$token) return false;
        $session = \App\Core\Application::getInstance()->getService('session');
        $stored = $session?->get(self::SESSION_KEY);
        if (!$stored) return false;
        return hash_equals((string) $stored, $token);
    }

    public function verify(): void
    {
        $method = $this->request->method();
        if (in_array($method, ['GET','HEAD','OPTIONS'], true)) return;

        $token = $this->request->post('_csrf') ?? $this->request->header('x-csrf-token');
        if (!$this->validate($token)) {
            throw new RuntimeException('CSRF token mismatch', 419);
        }
    }

    public function regenerate(): string
    {
        $session = \App\Core\Application::getInstance()->getService('session');
        $new = bin2hex(random_bytes(32));
        $session?->set(self::SESSION_KEY, $new);
        return $new;
    }
}
