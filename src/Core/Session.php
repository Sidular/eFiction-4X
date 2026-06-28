<?php

declare(strict_types=1);

namespace eFiction\Core;

/**
 * Session management with secure defaults and flash messages.
 */
class Session
{
    private readonly int $lifetime;
    private readonly string $name;

    public function __construct(private readonly array $config)
    {
        $this->name = $config['name'] ?? 'efiction_session';
        $this->lifetime = (int) ($config['lifetime'] ?? 86400);
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->name);

        $secure = (bool) ($this->config['secure'] ?? (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'));
        $httponly = (bool) ($this->config['httponly'] ?? true);
        $samesite = (string) ($this->config['samesite'] ?? 'Lax');

        session_set_cookie_params([
            'lifetime' => $this->lifetime,
            'path'     => '/',
            'domain'   => $this->config['domain'] ?? '',
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);

        session_start();

        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > $this->lifetime / 2) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value = null): mixed
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

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['flash'][$key]);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function id(): string
    {
        return session_id();
    }
}
