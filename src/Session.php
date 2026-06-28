<?php

declare(strict_types=1);

namespace eFiction;

/**
 * Session management with secure defaults.
 */
class Session
{
    public function __construct(private readonly array $config) {}

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $name = $this->config['name'] ?? 'efiction_session';
        session_name($name);

        $lifetime = (int) ($this->config['lifetime'] ?? 86400);
        $secure = (bool) ($this->config['secure'] ?? (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'));
        $httponly = (bool) ($this->config['httponly'] ?? true);
        $samesite = $this->config['samesite'] ?? 'Lax';

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => $this->config['domain'] ?? '',
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);

        session_start();

        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > $lifetime / 2) {
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
}
