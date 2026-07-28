<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use UnexpectedValueException;
use DomainException;
use InvalidArgumentException;
use RuntimeException;

/**
 * JWT (JSON Web Token) service.
 * 
 * Issues and validates access + refresh tokens.
 * Uses HS256 by default (configurable).
 */
final class JwtService
{
    public function __construct(
        private readonly Database $db,
        private readonly array $config
    ) {}

    /**
     * Issue an access token for the given user.
     */
    public function issueAccessToken(int $userId, array $claims = []): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => $this->config['issuer'],
            'aud' => $this->config['audience'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + (int) $this->config['access_ttl'],
            'jti' => bin2hex(random_bytes(16)),
            'sub' => (string) $userId,
            'typ' => 'access',
        ], $claims);
        return JWT::encode($payload, $this->config['secret'], $this->config['algo']);
    }

    /**
     * Issue and persist a refresh token.
     */
    public function issueRefreshToken(int $userId, ?string $device = null, ?string $ip = null, ?string $ua = null): string
    {
        $token = bin2hex(random_bytes(48));
        $hash  = hash('sha256', $token);
        $ttl   = (int) $this->config['refresh_ttl'];
        $this->db->insert('refresh_tokens', [
            'user_id'    => $userId,
            'token_hash' => $hash,
            'device'     => $device,
            'ip'         => $ip,
            'user_agent' => $ua,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
        ]);
        return $token;
    }

    /**
     * Validate and decode an access token.
     */
    public function validate(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config['secret'], $this->config['algo']));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new RuntimeException('Token expired', 401, $e);
        } catch (SignatureInvalidException $e) {
            throw new RuntimeException('Invalid signature', 401, $e);
        } catch (BeforeValidException $e) {
            throw new RuntimeException('Token not yet valid', 401, $e);
        } catch (UnexpectedValueException | DomainException $e) {
            throw new RuntimeException('Malformed token', 401, $e);
        }
    }

    /**
     * Try to validate, return null on failure instead of throwing.
     */
    public function tryValidate(string $token): ?array
    {
        try {
            return $this->validate($token);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Decode token without verification (debug only).
     */
    public function decodeUnsafe(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new InvalidArgumentException('Malformed token');
        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($payload === false) throw new InvalidArgumentException('Malformed payload');
        return (array) json_decode($payload, true);
    }

    /**
     * Revoke a refresh token.
     */
    public function revokeRefreshToken(string $token): bool
    {
        $hash = hash('sha256', $token);
        $rows = $this->db->update(
            'refresh_tokens',
            ['revoked_at' => date('Y-m-d H:i:s')],
            'token_hash = :h AND revoked_at IS NULL',
            ['h' => $hash]
        );
        return $rows > 0;
    }

    /**
     * Revoke all refresh tokens for a user.
     */
    public function revokeAllForUser(int $userId): int
    {
        return $this->db->update(
            'refresh_tokens',
            ['revoked_at' => date('Y-m-d H:i:s')],
            'user_id = :u AND revoked_at IS NULL',
            ['u' => $userId]
        );
    }

    /**
     * Verify a refresh token is valid and unexpired.
     */
    public function verifyRefreshToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $row = $this->db->fetchOne(
            'SELECT * FROM refresh_tokens WHERE token_hash = :h AND revoked_at IS NULL AND expires_at > NOW() LIMIT 1',
            ['h' => $hash]
        );
        return $row ?: null;
    }

    public function ttl(): int { return (int) $this->config['access_ttl']; }
    public function refreshTtl(): int { return (int) $this->config['refresh_ttl']; }
}
