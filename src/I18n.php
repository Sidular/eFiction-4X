<?php

declare(strict_types=1);

namespace eFiction;

/**
 * Lightweight internationalization layer using PHP language arrays.
 */
class I18n
{
    private array $strings = [];

    public function __construct(private string $language = 'en')
    {
        $this->load($language);
    }

    public function load(string $language): void
    {
        $this->language = $language;
        $file = __DIR__ . '/../languages/' . $language . '.php';
        if (file_exists($file)) {
            $strings = require $file;
            $this->strings = is_array($strings) ? $strings : [];
        } else {
            $this->strings = [];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->strings[$key] ?? $default ?? $key;
    }

    public function set(string $key, string $value): void
    {
        $this->strings[$key] = $value;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function all(): array
    {
        return $this->strings;
    }
}
