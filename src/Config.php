<?php

declare(strict_types=1);

namespace eFiction;

/**
 * Immutable configuration accessor with dot-notation support.
 */
class Config
{
    public function __construct(private readonly array $data) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->data;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function has(string $key): bool
    {
        $value = $this->data;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }
        return true;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function prefix(): string
    {
        return (string) $this->get('db.prefix', 'fanfiction_');
    }

    public function siteKey(): string
    {
        return (string) $this->get('site.key', 'default');
    }

    public function storiesPath(): string
    {
        $path = $this->get('site.stories_path', __DIR__ . '/../storage/stories');
        return rtrim($path, '/');
    }

    public function imagesPath(): string
    {
        $path = $this->get('site.images_path', __DIR__ . '/../storage/images');
        return rtrim($path, '/');
    }
}
