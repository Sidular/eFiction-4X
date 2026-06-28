<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\App;
use eFiction\Auth;
use eFiction\Config;
use eFiction\Database;
use eFiction\Helpers;
use eFiction\I18n;
use eFiction\Security;
use eFiction\Session;
use eFiction\Template;

/**
 * Shared controller utilities and service accessors.
 */
class BaseController
{
    public function __construct(protected App $app) {}

    protected function db(): Database
    {
        return $this->app->get(Database::class);
    }

    protected function config(): Config
    {
        return $this->app->get(Config::class);
    }

    protected function template(): Template
    {
        return $this->app->get(Template::class);
    }

    protected function auth(): Auth
    {
        return $this->app->get(Auth::class);
    }

    protected function security(): Security
    {
        return $this->app->get(Security::class);
    }

    protected function session(): Session
    {
        return $this->app->get(Session::class);
    }

    protected function i18n(): I18n
    {
        return $this->app->get(I18n::class);
    }

    protected function render(string $view, array $data = []): string
    {
        return $this->template()->render($view, $data);
    }

    protected function redirect(string $path): void
    {
        Helpers::redirect($path);
    }

    protected function flash(string $key, mixed $value = null): mixed
    {
        return Helpers::flash($key, $value);
    }

    protected function requireAuth(): void
    {
        $this->auth()->requireAuth();
    }

    protected function requireAdmin(): void
    {
        $this->auth()->requireAdmin();
    }

    protected function int(string $key): int
    {
        return $this->security()->int($key);
    }

    protected function string(string $key, string $default = ''): string
    {
        return $this->security()->string($key, $default);
    }

    protected function csrf(): string
    {
        return $this->security()->generateToken();
    }

    protected function validateCsrf(): bool
    {
        return $this->security()->validateToken($this->string('csrf_token'));
    }

    protected function url(string $path): string
    {
        return Helpers::baseUrl() . '/' . ltrim($path, '/');
    }
}
