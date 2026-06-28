<?php

declare(strict_types=1);

namespace eFiction;

/**
 * Security helpers: CSRF tokens, password hashing, input sanitization.
 */
class Security
{
    public function generateToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function regenerateToken(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function slug(string $text, int $max = 80): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\-]+/u', '-', $text) ?? '';
        $text = trim($text, '-');
        $text = preg_replace('/-+/', '-', $text) ?? '';
        return mb_substr($text, 0, $max);
    }

    public function descript(string $text): string
    {
        return htmlspecialchars(strip_tags($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function allowedTags(string $text, array $tags = ['p','br','strong','em','u','a','ul','ol','li','blockquote','h1','h2','h3','h4','img']): string
    {
        return strip_tags($text, $tags);
    }

    public function int(string $name): int
    {
        return filter_input(INPUT_GET, $name, FILTER_VALIDATE_INT) ??
               filter_input(INPUT_POST, $name, FILTER_VALIDATE_INT) ?? 0;
    }

    public function string(string $name, string $default = ''): string
    {
        $value = $_GET[$name] ?? $_POST[$name] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public function email(string $email): string|false
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ?: false;
    }
}
