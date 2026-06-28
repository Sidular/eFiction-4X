<?php

declare(strict_types=1);

namespace eFiction\Core;

/**
 * HTTP response helper.
 */
class Response
{
    public function redirect(string $url, int $code = 302): never
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    public function back(string $fallback = '/'): never
    {
        $url = $_SERVER['HTTP_REFERER'] ?? $fallback;
        $this->redirect($url);
    }

    public function json(array $data, int $code = 200): string
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function text(string $text, int $code = 200): string
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        return $text;
    }

    public function xml(string $xml, int $code = 200): string
    {
        http_response_code($code);
        header('Content-Type: application/xml; charset=utf-8');
        return $xml;
    }

    public function rss(string $xml): string
    {
        return $this->xml($xml, 200);
    }

    public function setHeader(string $name, string $value): self
    {
        header("{$name}: {$value}");
        return $this;
    }

    public function notFound(): void
    {
        http_response_code(404);
    }

    public function forbidden(): void
    {
        http_response_code(403);
    }

    public function error(int $code = 500): void
    {
        http_response_code($code);
    }
}
