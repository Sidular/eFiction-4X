<?php

declare(strict_types=1);

namespace eFiction\Core;

/**
 * Security helpers: CSRF tokens, password hashing, input sanitization, slugs.
 */
class Security
{
    private readonly int $cost;

    public function __construct(private readonly Config $config)
    {
        $this->cost = (int) $config->get('security.password_cost', 12);
    }

    public function csrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrf(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function regenerateCsrf(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    public function randomToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public function slug(string $text, int $max = 80): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\-]+/u', '-', $text) ?? '';
        $text = trim($text, '-');
        $text = preg_replace('/-+/', '-', $text) ?? '';
        return mb_substr($text, 0, $max);
    }

    public function e(string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function stripTags(string $text, array $allowed = ['p','br','strong','em','u','a','ul','ol','li','blockquote','h1','h2','h3','h4','img']): string
    {
        return strip_tags($text, $allowed);
    }

    public function descript(string $text): string
    {
        return $this->e(strip_tags($text));
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return is_numeric($value) ? (int) $value : $default;
    }

    public function email(string $email): string|false
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ?: false;
    }
}
