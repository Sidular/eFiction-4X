<?php

declare(strict_types=1);

namespace eFiction\Core;

/**
 * Lightweight dependency injection container.
 */
class Container
{
    private array $registry = [];
    private array $singletons = [];

    public function set(string $id, callable $factory): self
    {
        $this->registry[$id] = $factory;
        return $this;
    }

    public function singleton(string $id, callable $factory): self
    {
        $this->registry[$id] = function (Container $container) use ($id, $factory) {
            if (!isset($this->singletons[$id])) {
                $this->singletons[$id] = $factory($container);
            }
            return $this->singletons[$id];
        };
        return $this;
    }

    public function get(string $id): mixed
    {
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        if (!isset($this->registry[$id])) {
            throw new \RuntimeException("Service not found: {$id}");
        }

        $factory = $this->registry[$id];
        return $factory($this);
    }

    public function has(string $id): bool
    {
        return isset($this->registry[$id]) || isset($this->singletons[$id]);
    }
}
