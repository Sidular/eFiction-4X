<?php

declare(strict_types=1);

namespace eFiction\Core;

use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Router supporting pretty URLs, legacy query strings, and parameter injection.
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $groupStack = [];

    public function get(string $pattern, callable|string $handler, array $middleware = []): self
    {
        return $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable|string $handler, array $middleware = []): self
    {
        return $this->add('POST', $pattern, $handler, $middleware);
    }

    public function any(string $pattern, callable|string $handler, array $middleware = []): self
    {
        return $this->add('GET', $pattern, $handler, $middleware)
                    ->add('POST', $pattern, $handler, $middleware);
    }

    public function middleware(callable $handler): self
    {
        $this->middleware[] = $handler;
        return $this;
    }

    public function group(string $prefix, callable $routes, array $middleware = []): self
    {
        $this->groupStack[] = ['prefix' => $prefix, 'middleware' => $middleware];
        $routes($this);
        array_pop($this->groupStack);
        return $this;
    }

    private function add(string $method, string $pattern, callable|string $handler, array $middleware = []): self
    {
        $prefix = '';
        $groupMiddleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= rtrim($group['prefix'], '/');
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware'] ?? []);
        }
        $pattern = $prefix . '/' . ltrim($pattern, '/');
        $pattern = '/' . ltrim($pattern, '/');

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => array_merge($this->middleware, $groupMiddleware, $middleware),
        ];
        return $this;
    }

    public function dispatch(App $app): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->resolveUri();
        $params = $_GET;

        foreach ($this->routes[$method] ?? [] as $route) {
            $matches = $this->match($route['pattern'], $uri);
            if ($matches === null) {
                continue;
            }

            $routeParams = array_merge($params, $matches);
            foreach ($route['middleware'] as $mw) {
                $result = $this->call($app, $mw, $routeParams);
                if ($result === false) {
                    return;
                }
            }

            $response = $this->call($app, $route['handler'], $routeParams);
            if (is_string($response)) {
                echo $response;
            } elseif (is_array($response)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($response, JSON_THROW_ON_ERROR);
            }
            return;
        }

        $this->notFound($app);
    }

    private function resolveUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (str_starts_with($uri, $script)) {
            $uri = substr($uri, strlen($script));
        }
        return '/' . ltrim($uri, '/');
    }

    private function match(string $pattern, string $uri): ?array
    {
        $pattern = preg_replace('/\{(\w+):([^}]+)\}/', '(?<$1>$2)', $pattern);
        $pattern = preg_replace('/\{(\w+)\}/', '(?<$1>[^/]+)', $pattern);
        $regex = '#^' . $pattern . '$#';
        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    private function call(App $app, callable|string $handler, array $params): mixed
    {
        if (is_string($handler)) {
            $handler = $app->get($handler);
        }

        if (is_array($handler)) {
            [$class, $method] = $handler;
            if (!is_object($class)) {
                $class = $app->get($class);
            }
            $ref = new ReflectionMethod($class, $method);
            $invoke = [$class, $method];
        } else {
            $ref = new ReflectionFunction($handler);
            $invoke = $handler;
        }

        $args = [];
        foreach ($ref->getParameters() as $param) {
            $type = $param->getType();
            $name = $param->getName();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $className = $type->getName();
                $args[] = $app->get($className);
            } elseif (isset($params[$name])) {
                $args[] = $params[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return $invoke(...$args);
    }

    private function notFound(App $app): void
    {
        http_response_code(404);
        $template = $app->get(Template::class);
        echo $template->render('error', ['code' => 404, 'message' => 'Page not found.']);
    }
}
