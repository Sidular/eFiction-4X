<?php

declare(strict_types=1);

namespace eFiction\Core;

/**
 * HTTP request wrapper with safe typed accessors.
 */
class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->post) || array_key_exists($key, $this->get);
    }

    public function hasPost(string $key): bool
    {
        return array_key_exists($key, $this->post);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);
        if (is_numeric($value)) {
            return (int) $value;
        }
        return $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->input($key, $default);
        if (is_numeric($value)) {
            return (float) $value;
        }
        return $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_scalar($value) ? (string) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->input($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public function array(string $key): array
    {
        $value = $this->input($key, []);
        return is_array($value) ? $value : [];
    }

    public function email(string $key, string $default = ''): string
    {
        $value = $this->string($key, $default);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ?: $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?: '/';
    }

    public function queryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    public function scheme(): string
    {
        $https = $this->server['HTTPS'] ?? '';
        return (!empty($https) && $https !== 'off') ? 'https' : 'http';
    }

    public function host(): string
    {
        return $this->server['HTTP_HOST'] ?? 'localhost';
    }

    public function baseUrl(): string
    {
        return $this->scheme() . '://' . $this->host();
    }

    public function referer(): string
    {
        return $this->server['HTTP_REFERER'] ?? '';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function isSecure(): bool
    {
        return $this->scheme() === 'https';
    }
}
