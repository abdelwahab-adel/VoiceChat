<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use InvalidArgumentException;

/**
 * Authentication service.
 * 
 * Handles:
 *  - Registration
 *  - Login (password / JWT)
 *  - Logout
 *  - Profile / session management
 *  - Permission checks
 *  - Online status
 */
final class AuthService
{
    private static ?self $instance = null;
    private ?array $user = null;
    private ?int $userId = null;

    public function __construct(
        private readonly Database $db,
        private readonly JwtService $jwt,
        private readonly Request $request
    ) {
        self::$instance = $this;
        $this->bootstrap();
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            throw new RuntimeException('AuthService not initialized');
        }
        return self::$instance;
    }

    /**
     * Try to resolve the current user from token or session.
     */
    private function bootstrap(): void
    {
        $token = $this->request->bearerToken();
        if ($token) {
            $claims = $this->jwt->tryValidate($token);
            if ($claims && isset($claims['sub'])) {
                $this->userId = (int) $claims['sub'];
                $this->loadUser();
                return;
            }
        }
        // Fall back to session
        $session = \App\Core\Application::getInstance()->getService('session');
        $uid = $session?->get('user_id');
        if ($uid) {
            $this->userId = (int) $uid;
            $this->loadUser();
        }
    }

    private function loadUser(): void
    {
        $row = $this->db->fetchOne(
            'SELECT id,uuid,username,email,display_name,bio,avatar,cover,role,status,
                    online_status,last_seen_at,coins,xp,level,vip_level,is_verified,is_featured,
                    email_verified_at,settings,social_links
             FROM users WHERE id = :id LIMIT 1',
            ['id' => $this->userId]
        );
        if ($row && $row['status'] === 'active') {
            $this->user = $row;
        } else {
            $this->user = null;
            $this->userId = null;
        }
    }

    public function user(): ?array { return $this->user; }
    public function id(): ?int { return $this->userId; }
    public function check(): bool { return $this->user !== null; }
    public function guest(): bool { return $this->user === null; }

    public function setUser(array $user): void
    {
        $this->user = $user;
        $this->userId = (int) $user['id'];
    }

    public function isAdmin(): bool
    {
        return $this->user && in_array($this->user['role'], ['admin','superadmin'], true);
    }

    public function isModerator(): bool
    {
        return $this->user && in_array($this->user['role'], ['admin','superadmin','moderator'], true);
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->user && in_array($this->user['role'], $roles, true);
    }

    public function can(string $permission): bool
    {
        if (!$this->user) return false;
        $role = $this->user['role'];
        return match ($permission) {
            'manage_users', 'manage_rooms', 'manage_agencies', 'manage_gifts',
            'manage_reports', 'view_logs', 'manage_settings', 'ban_users'
                => in_array($role, ['admin','superadmin'], true),
            'moderate_rooms' => in_array($role, ['admin','superadmin','moderator'], true),
            'create_room', 'send_gift', 'send_message', 'create_agency'
                => $this->user['status'] === 'active',
            default => false,
        };
    }

    /**
     * Register a new user.
     */
    public function register(array $data, ?string $ip = null, ?string $ua = null): array
    {
        $required = ['username','email','password'];
        foreach ($required as $f) {
            if (empty($data[$f])) throw new InvalidArgumentException("$f is required");
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
        if (strlen($data['password']) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters');
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $data['username'])) {
            throw new InvalidArgumentException('Username must be 3-30 chars, alphanumeric or underscore');
        }
        if ($this->db->fetchValue('SELECT id FROM users WHERE email = :e LIMIT 1', ['e' => $data['email']])) {
            throw new InvalidArgumentException('Email already registered');
        }
        if ($this->db->fetchValue('SELECT id FROM users WHERE username = :u LIMIT 1', ['u' => $data['username']])) {
            throw new InvalidArgumentException('Username already taken');
        }

        $userId = $this->db->insert('users', [
            'uuid'         => Uuid::uuid4()->toString(),
            'username'     => $data['username'],
            'email'        => $data['email'],
            'phone'        => $data['phone'] ?? null,
            'password'     => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'display_name' => $data['display_name'] ?? $data['username'],
            'gender'       => $data['gender'] ?? null,
            'country'      => $data['country'] ?? null,
            'language'     => $data['language'] ?? 'en',
            'coins'        => (int) ($_ENV['WELCOME_COINS'] ?? 100),
            'status'       => 'active',
            'role'         => 'user',
            'online_status'=> 'offline',
            'last_seen_at' => date('Y-m-d H:i:s'),
            'email_verified_at' => ($_ENV['APP_ENV'] ?? 'production') === 'local' ? date('Y-m-d H:i:s') : null,
        ]);

        // Welcome bonus
        $this->db->insert('coin_transactions', [
            'user_id'        => $userId,
            'type'           => 'reward',
            'amount'         => (int) ($_ENV['WELCOME_COINS'] ?? 100),
            'balance_after'  => (int) ($_ENV['WELCOME_COINS'] ?? 100),
            'description'    => 'Welcome bonus',
        ]);

        // Log
        $this->logActivity($userId, 'register', 'user', $userId, $ip, $ua);

        return ['id' => (int) $userId, 'username' => $data['username'], 'email' => $data['email']];
    }

    /**
     * Login by email/username + password.
     */
    public function login(string $login, string $password, ?string $ip = null, ?string $ua = null, ?string $device = null): array
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM users WHERE (email = :l OR username = :l) LIMIT 1',
            ['l' => $login]
        );
        if (!$row) {
            $this->logLogin(null, $login, $ip, $ua, 'failed', 'User not found');
            throw new InvalidArgumentException('Invalid credentials');
        }
        if ($row['status'] === 'banned') {
            $this->logLogin($row['id'], $login, $ip, $ua, 'blocked', 'Banned user');
            throw new RuntimeException('Account is banned', 403);
        }
        if ($row['status'] !== 'active') {
            $this->logLogin($row['id'], $login, $ip, $ua, 'failed', 'Inactive account');
            throw new RuntimeException('Account is not active', 403);
        }
        if (!password_verify($password, $row['password'])) {
            $this->logLogin($row['id'], $login, $ip, $ua, 'failed', 'Wrong password');
            throw new InvalidArgumentException('Invalid credentials');
        }
        $this->db->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
            'online_status' => 'online',
            'last_seen_at'  => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $row['id']]);

        $this->logLogin($row['id'], $login, $ip, $ua, 'success');
        $this->logActivity($row['id'], 'login', 'user', $row['id'], $ip, $ua);

        $this->userId = (int) $row['id'];
        $this->loadUser();

        $tokens = $this->generateTokens($ip, $ua, $device);

        return [
            'user'   => $this->publicUser($this->user),
            'tokens' => $tokens,
        ];
    }

    /**
     * Login / register via third-party (placeholder for OAuth).
     */
    public function loginWithProvider(string $provider, array $profile): array
    {
        // Stub - implement Google/Facebook/etc.
        throw new RuntimeException('Provider not configured');
    }

    /**
     * Generate a fresh access+refresh token pair.
     */
    public function generateTokens(?string $ip = null, ?string $ua = null, ?string $device = null): array
    {
        if (!$this->userId) throw new RuntimeException('Not authenticated');
        $access  = $this->jwt->issueAccessToken($this->userId, [
            'username' => $this->user['username'] ?? null,
            'role'     => $this->user['role'] ?? 'user',
        ]);
        $refresh = $this->jwt->issueRefreshToken($this->userId, $device, $ip, $ua);
        return [
            'access_token'  => $access,
            'refresh_token' => $refresh,
            'token_type'    => 'Bearer',
            'expires_in'    => $this->jwt->ttl(),
        ];
    }

    /**
     * Refresh access token using a valid refresh token.
     */
    public function refresh(string $refreshToken, ?string $ip = null, ?string $ua = null): array
    {
        $row = $this->jwt->verifyRefreshToken($refreshToken);
        if (!$row) throw new RuntimeException('Invalid refresh token', 401);
        $this->userId = (int) $row['user_id'];
        $this->loadUser();
        if (!$this->user) throw new RuntimeException('User not found', 401);
        // Rotate
        $this->jwt->revokeRefreshToken($refreshToken);
        return $this->generateTokens($ip, $ua);
    }

    /**
     * Logout the current user.
     */
    public function logout(?string $refreshToken = null): bool
    {
        if ($refreshToken) $this->jwt->revokeRefreshToken($refreshToken);
        if ($this->userId) {
            $this->db->update('users', [
                'online_status' => 'offline',
                'last_seen_at'  => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $this->userId]);
            $this->logActivity($this->userId, 'logout', 'user', $this->userId, $this->request->ip(), $this->request->userAgent());
        }
        $session = \App\Core\Application::getInstance()->getService('session');
        $session?->forget('user_id');
        $this->user = null;
        $this->userId = null;
        return true;
    }

    /**
     * Start a server-side session (for web).
     */
    public function startSession(): void
    {
        $session = \App\Core\Application::getInstance()->getService('session');
        if ($this->userId) {
            $session?->set('user_id', $this->userId);
            $session?->regenerate();
        }
    }

    /**
     * Update password (current -> new).
     */
    public function changePassword(int $userId, string $current, string $new): bool
    {
        $row = $this->db->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => $userId]);
        if (!$row || !password_verify($current, $row['password'])) {
            throw new InvalidArgumentException('Current password is incorrect');
        }
        $this->db->update('users', [
            'password' => password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]),
        ], 'id = :id', ['id' => $userId]);
        $this->jwt->revokeAllForUser($userId);
        return true;
    }

    /**
     * Public user shape.
     */
    public function publicUser(?array $user = null): ?array
    {
        $u = $user ?? $this->user;
        if (!$u) return null;
        return [
            'id'            => (int) $u['id'],
            'uuid'          => $u['uuid'],
            'username'      => $u['username'],
            'display_name'  => $u['display_name'] ?? $u['username'],
            'avatar'        => $u['avatar'] ? url('public/' . $u['avatar']) : null,
            'cover'         => $u['cover']  ? url('public/' . $u['cover'])  : null,
            'bio'           => $u['bio'] ?? null,
            'level'         => (int) ($u['level'] ?? 1),
            'vip_level'     => (int) ($u['vip_level'] ?? 0),
            'is_verified'   => (bool) ($u['is_verified'] ?? false),
            'online_status' => $u['online_status'] ?? 'offline',
            'role'          => $u['role'] ?? 'user',
        ];
    }

    private function logLogin(?int $userId, string $email, ?string $ip, ?string $ua, string $status, ?string $reason = null): void
    {
        $this->db->insert('login_history', [
            'user_id'   => $userId,
            'email'     => $email,
            'ip'        => $ip,
            'user_agent'=> $ua,
            'status'    => $status,
            'reason'    => $reason,
        ]);
    }

    public function logActivity(int $userId, string $action, ?string $subjectType = null, ?int $subjectId = null, ?string $ip = null, ?string $ua = null, array $metadata = []): void
    {
        $this->db->insert('activity_logs', [
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'ip'           => $ip,
            'user_agent'   => $ua,
            'metadata'     => $metadata ? json_encode($metadata) : null,
        ]);
    }
}
