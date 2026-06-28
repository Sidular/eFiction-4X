<?php

declare(strict_types=1);

namespace eFiction;

/**
 * Router that handles both pretty URLs and legacy eFiction query strings.
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];

    public function get(string $pattern, callable $handler): self
    {
        return $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): self
    {
        return $this->add('POST', $pattern, $handler);
    }

    public function any(string $pattern, callable $handler): self
    {
        return $this->add('GET', $pattern, $handler)->add('POST', $pattern, $handler);
    }

    public function middleware(callable $handler): self
    {
        $this->middleware[] = $handler;
        return $this;
    }

    private function add(string $method, string $pattern, callable $handler): self
    {
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
        ];
        return $this;
    }

    public function dispatch(App $app): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->resolveUri();
        $params = $_GET;

        foreach ($this->middleware as $mw) {
            $result = $mw($app);
            if ($result === false) {
                return;
            }
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if ($matches = $this->match($route['pattern'], $uri)) {
                $this->call($app, $route['handler'], array_merge($params, $matches));
                return;
            }
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

    private function call(App $app, callable $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            if (!is_object($class)) {
                $class = $app->get($class);
            }
            $ref = new \ReflectionMethod($class, $method);
            $invoke = [$class, $method];
        } else {
            $ref = new \ReflectionFunction($handler);
            $invoke = $handler;
        }

        $args = [];
        foreach ($ref->getParameters() as $param) {
            $type = $param->getType();
            $name = $param->getName();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
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
        $response = $invoke(...$args);
        if (is_string($response)) {
            echo $response;
        } elseif (is_array($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    }

    private function notFound(App $app): void
    {
        http_response_code(404);
        $template = $app->get(Template::class);
        echo $template->render('error', ['code' => 404, 'message' => 'Page not found.']);
    }
}
