<?php

declare(strict_types=1);

namespace eFiction;

/**
 * Lightweight PHP-based template engine using output buffering and layouts.
 */
class Template
{
    private string $basePath;
    private string $layout = 'default';
    private array $sections = [];
    private array $data = [];

    public function __construct(Config $config, private I18n $i18n)
    {
        $this->basePath = __DIR__ . '/../templates';
    }

    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function section(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    public function start(string $name): void
    {
        ob_start();
        $this->currentSection = $name;
    }

    public function end(): void
    {
        $this->sections[$this->currentSection] = ob_get_clean() ?: '';
        unset($this->currentSection);
    }

    public function render(string $view, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);
        extract($this->data, EXTR_SKIP);

        $this->start('content');
        $viewFile = $this->basePath . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Template not found: {$view}");
        }
        require $viewFile;
        $this->end();

        $layoutFile = $this->basePath . '/layouts/' . $this->layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$this->layout}");
        }
        ob_start();
        require $layoutFile;
        return ob_get_clean() ?: '';
    }

    public function partial(string $name, array $data = []): string
    {
        $file = $this->basePath . '/partials/' . $name . '.php';
        if (!file_exists($file)) {
            return '';
        }
        extract(array_merge($this->data, $data), EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean() ?: '';
    }

    public function __(string $key): string
    {
        return (string) $this->i18n->get($key);
    }

    public function e(string $text): string
    {
        return Helpers::e($text);
    }
}
