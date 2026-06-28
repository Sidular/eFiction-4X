<?php

declare(strict_types=1);

namespace eFiction;

/**
 * Simple service container and application bootstrap.
 */
class App
{
    private array $registry = [];
    private array $singletons = [];
    private bool $booted = false;

    public function __construct(private readonly array $config) {}

    public function get(string $id): mixed
    {
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        if (isset($this->registry[$id])) {
            $factory = $this->registry[$id];
            return $factory($this);
        }

        throw new \RuntimeException("Service not found: {$id}");
    }

    public function set(string $id, callable $factory): void
    {
        $this->registry[$id] = $factory;
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->registry[$id] = function (App $app) use ($id, $factory) {
            if (!isset($this->singletons[$id])) {
                $this->singletons[$id] = $factory($app);
            }
            return $this->singletons[$id];
        };
    }

    public function config(): array
    {
        return $this->config;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        date_default_timezone_set($this->config['site']['timezone'] ?? 'UTC');
        error_reporting(E_ALL);

        if (!($this->config['debug'] ?? false)) {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else {
            ini_set('display_errors', '1');
        }
    }

    public function run(): void
    {
        $router = $this->get(Router::class);
        $router->dispatch($this);
    }
}
