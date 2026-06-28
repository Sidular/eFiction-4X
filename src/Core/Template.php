<?php

declare(strict_types=1);

namespace eFiction\Core;

/**
 * Lightweight PHP-based template engine with skin/layout support.
 */
class Template
{
    private string $basePath;
    private string $layout = 'default';
    private string $skin = 'default';
    private ?string $currentSection = null;
    private array $sections = [];
    private array $data = [];

    public function __construct(
        private readonly Config $config,
        private readonly I18n $i18n,
        private readonly Auth $auth,
        private readonly Security $security
    ) {
        $this->basePath = $this->config->skinsPath();
    }

    public function setSkin(string $skin): self
    {
        $this->skin = $skin;
        return $this;
    }

    public function skin(): string
    {
        return $this->skin;
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
        if ($this->currentSection === null) {
            return;
        }
        $this->sections[$this->currentSection] = ob_get_clean() ?: '';
        $this->currentSection = null;
    }

    public function render(string $view, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);
        $this->data['auth'] = $this->auth;
        $this->data['template'] = $this;
        $this->data['config'] = $this->config;

        $this->start('content');
        $viewFile = $this->viewPath($view);
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Template not found: {$view} (skin: {$this->skin})");
        }
        extract($this->data, EXTR_SKIP);
        require $viewFile;
        $this->end();

        $layoutFile = $this->layoutPath($this->layout);
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$this->layout} (skin: {$this->skin})");
        }
        ob_start();
        extract($this->data, EXTR_SKIP);
        require $layoutFile;
        return ob_get_clean() ?: '';
    }

    public function partial(string $name, array $data = []): string
    {
        $file = $this->basePath . '/' . $this->skin . '/partials/' . $name . '.php';
        if (!file_exists($file)) {
            $file = $this->basePath . '/default/partials/' . $name . '.php';
        }
        if (!file_exists($file)) {
            return '';
        }
        $merged = array_merge($this->data, $data);
        extract($merged, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean() ?: '';
    }

    public function block(string $name, array $data = []): string
    {
        return $this->partial('blocks/' . $name, $data);
    }

    public function exists(string $view): bool
    {
        return file_exists($this->viewPath($view));
    }

    public function __(): string
    {
        return (string) $this->i18n->get(...func_get_args());
    }

    public function e(string $text): string
    {
        return $this->security->e($text);
    }

    public function route(string $path, array $params = []): string
    {
        $query = $params ? '?' . http_build_query($params) : '';
        return '/' . ltrim($path, '/') . $query;
    }

    public function baseUrl(): string
    {
        return $this->config->get('site.url', '');
    }

    public function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }

    public function csrf(): string
    {
        return $this->security->csrf();
    }

    private function viewPath(string $view): string
    {
        $file = $this->basePath . '/' . $this->skin . '/' . $view . '.php';
        if (!file_exists($file)) {
            $file = $this->basePath . '/default/' . $view . '.php';
        }
        return $file;
    }

    private function layoutPath(string $layout): string
    {
        $file = $this->basePath . '/' . $this->skin . '/layouts/' . $layout . '.php';
        if (!file_exists($file)) {
            $file = $this->basePath . '/default/layouts/' . $layout . '.php';
        }
        return $file;
    }
}
