<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTService
{
    /**
     * Generate a JWT token for the user.
     *
     * @param int $userId
     * @param string $username
     * @param string $roleCode
     * @param string $sessionToken
     * @param int $expirySeconds
     * @return string
     */
    public static function generateToken(int $userId, string $username, string $roleCode, string $sessionToken, int $expirySeconds = 86400): string
    {
        $key = config('app.key');
        // Ensure key is clean
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $payload = [
            'iss' => config('app.url'),
            'sub' => $userId,
            'username' => $username,
            'role' => $roleCode,
            'session_token' => $sessionToken,
            'iat' => time(),
            'exp' => time() + $expirySeconds,
        ];

        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Decode and validate a JWT token.
     *
     * @param string $token
     * @return object|null
     */
    public static function decodeToken(string $token): ?object
    {
        try {
            $key = config('app.key');
            if (str_starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            return JWT::decode($token, new Key($key, 'HS256'));
        } catch (Exception $e) {
            return null;
        }
    }
}
