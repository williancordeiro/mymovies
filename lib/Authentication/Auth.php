<?php

namespace Lib\Authentication;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class Auth
{
    private static ?string $key = null;
    private static string $algorithm = 'HS256';

    private static function getKey(): string
    {
        if (self::$key === null) {
            self::$key = $_ENV['JWT_SECRET'] ?? 'default_secret_key_with_32_characters_';
        }

        return self::$key;
    }

    public static function generateToken(User $user): string
    {
        $playload = [
            'iss' => 'mymovies',
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24),
            'sub' => $user->id,
            'email' => $user->email,
            'username' => $user->username,
            'admin' => (int) $user->admin
        ];

        return JWT::encode($playload, self::getKey(), self::$algorithm);
    }

    public static function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(self::getKey(), self::$algorithm));
        } catch (Exception $e) {
            return null;
        }
    }

    public static function user(string $token): ?User
    {
        $decoded = self::validateToken($token);
        if ($decoded && isset($decoded->sub)) {
            return User::findById($decoded->sub);
        }
        return null;
    }
}
