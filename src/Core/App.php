<?php

declare(strict_types=1);

namespace eFiction\Core;

use eFiction\Core\Exception\HttpException;

/**
 * Application bootstrap and runner.
 */
class App
{
    private bool $booted = false;

    public function __construct(
        private readonly Container $container,
        private readonly Config $config
    ) {}

    public function container(): Container
    {
        return $this->container;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function singleton(string $id, callable $factory): self
    {
        $this->container->singleton($id, $factory);
        return $this;
    }

    public function set(string $id, callable $factory): self
    {
        $this->container->set($id, $factory);
        return $this;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        date_default_timezone_set($this->config->get('site.timezone', 'UTC'));
        error_reporting(E_ALL);

        if ($this->config->get('debug', false)) {
            ini_set('display_errors', '1');
        } else {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        }

        $this->container->singleton(Config::class, fn() => $this->config);
    }

    public function run(): void
    {
        $this->boot();
        $router = $this->container->get(Router::class);
        $router->dispatch($this);
    }
}
