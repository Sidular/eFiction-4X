<?php

declare(strict_types=1);

namespace eFiction;

/**
 * Miscellaneous utility helpers.
 */
class Helpers
{
    public static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function route(string $path, array $params = []): string
    {
        $query = $params ? '?' . http_build_query($params) : '';
        return rtrim((string) ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/') . '/' . ltrim($path, '/') . $query;
    }

    public static function baseUrl(): string
    {
        return rtrim((string) ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    }

    public static function currentUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        if ($value === null) {
            $message = $_SESSION['flash'][$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old'][$key] ?? $default;
    }

    public static function setOld(array $data): void
    {
        $_SESSION['old'] = $data;
    }

    public static function clearOld(): void
    {
        unset($_SESSION['old']);
    }

    public static function truncate(string $text, int $length = 200): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '...';
    }

    public static function ago(?\DateTimeInterface $dt): string
    {
        if (!$dt) {
            return '';
        }
        $diff = (new \DateTimeImmutable())->getTimestamp() - $dt->getTimestamp();
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return (int)($diff / 60) . ' minutes ago';
        if ($diff < 86400) return (int)($diff / 3600) . ' hours ago';
        if ($diff < 604800) return (int)($diff / 86400) . ' days ago';
        return $dt->format('Y-m-d');
    }

    public static function pagination(int $total, int $page, int $perPage, string $baseUrl): array
    {
        $pages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $pages ?: 1));
        return [
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'perPage' => $perPage,
            'start' => ($page - 1) * $perPage,
            'hasPrev' => $page > 1,
            'hasNext' => $page < $pages,
            'baseUrl' => $baseUrl,
        ];
    }
}
