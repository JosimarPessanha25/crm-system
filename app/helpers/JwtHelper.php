<?php

declare(strict_types=1);

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;

class JwtHelper
{
    private string $secretKey;
    private string $algorithm;
    private int $accessTokenExpiry;
    private int $refreshTokenExpiry;
    private string $issuer;

    public function __construct(
        string $secretKey,
        int $accessTokenExpiry = 3600, // 1 hora
        int $refreshTokenExpiry = 604800, // 7 dias
        string $algorithm = 'HS256',
        string $issuer = 'crm-system'
    ) {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
        $this->accessTokenExpiry = $accessTokenExpiry;
        $this->refreshTokenExpiry = $refreshTokenExpiry;
        $this->issuer = $issuer;
    }

    /**
     * Generate access token for authenticated user
     */
    public function generateAccessToken(array $userData): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $this->accessTokenExpiry,
            'type' => 'access',
            'user_id' => $userData['id'],
            'email' => $userData['email'],
            'role' => $userData['role'] ?? 'user',
            'permissions' => $userData['permissions'] ?? []
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Generate refresh token for token renewal
     */
    public function generateRefreshToken(string $userId): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $this->refreshTokenExpiry,
            'type' => 'refresh',
            'user_id' => $userId,
            'jti' => uniqid('refresh_', true) // Unique token ID
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Generate both access and refresh tokens
     */
    public function generateTokenPair(array $userData): array
    {
        return [
            'access_token' => $this->generateAccessToken($userData),
            'refresh_token' => $this->generateRefreshToken($userData['id']),
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiry,
            'expires_at' => time() + $this->accessTokenExpiry
        ];
    }

    /**
     * Decode and validate JWT token
     */
    public function decodeToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, $this->algorithm));
        } catch (ExpiredException $e) {
            throw new Exception('Token expired', 401);
        } catch (SignatureInvalidException $e) {
            throw new Exception('Invalid token signature', 401);
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage(), 401);
        }
    }

    /**
     * Validate access token and return user data
     */
    public function validateAccessToken(string $token): array
    {
        $decoded = $this->decodeToken($token);

        if (!isset($decoded->type) || $decoded->type !== 'access') {
            throw new Exception('Invalid token type', 401);
        }

        if (isset($decoded->exp) && $decoded->exp < time()) {
            throw new Exception('Token expired', 401);
        }

        return [
            'user_id' => $decoded->user_id,
            'email' => $decoded->email,
            'role' => $decoded->role ?? 'user',
            'permissions' => $decoded->permissions ?? [],
            'issued_at' => $decoded->iat,
            'expires_at' => $decoded->exp
        ];
    }

    /**
     * Validate refresh token
     */
    public function validateRefreshToken(string $token): string
    {
        $decoded = $this->decodeToken($token);

        if (!isset($decoded->type) || $decoded->type !== 'refresh') {
            throw new Exception('Invalid token type', 401);
        }

        if (isset($decoded->exp) && $decoded->exp < time()) {
            throw new Exception('Refresh token expired', 401);
        }

        return $decoded->user_id;
    }

    /**
     * Extract token from Authorization header
     */
    public function extractTokenFromHeader(string $authHeader): ?string
    {
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }

    /**
     * Check if token is about to expire (within 5 minutes)
     */
    public function isTokenNearExpiry(string $token): bool
    {
        try {
            $decoded = $this->decodeToken($token);
            $timeUntilExpiry = $decoded->exp - time();
            return $timeUntilExpiry < 300; // 5 minutes
        } catch (Exception $e) {
            return true; // Consider invalid tokens as expired
        }
    }

    /**
     * Get token expiry timestamp
     */
    public function getTokenExpiry(string $token): ?int
    {
        try {
            $decoded = $this->decodeToken($token);
            return $decoded->exp ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Create token for password reset
     */
    public function generatePasswordResetToken(string $userId, string $email): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + 3600, // 1 hour expiry
            'type' => 'password_reset',
            'user_id' => $userId,
            'email' => $email,
            'jti' => uniqid('reset_', true)
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate password reset token
     */
    public function validatePasswordResetToken(string $token): array
    {
        $decoded = $this->decodeToken($token);

        if (!isset($decoded->type) || $decoded->type !== 'password_reset') {
            throw new Exception('Invalid token type', 401);
        }

        return [
            'user_id' => $decoded->user_id,
            'email' => $decoded->email,
            'jti' => $decoded->jti
        ];
    }
}