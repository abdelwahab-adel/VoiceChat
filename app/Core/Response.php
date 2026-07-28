<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Response abstraction.
 * 
 * Provides helpers for sending JSON, HTML, redirects, and file responses.
 */
final class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';
    private bool $sent = false;

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function json(mixed $data, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->send();
        return $this;
    }

    public function html(string $content, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = $content;
        $this->send();
        return $this;
    }

    public function text(string $content, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'text/plain; charset=utf-8';
        $this->body = $content;
        $this->send();
        return $this;
    }

    public function redirect(string $url, int $status = 302): self
    {
        $this->statusCode = $status;
        $this->headers['Location'] = $url;
        $this->send();
        return $this;
    }

    public function view(string $template, array $data = [], int $status = 200): self
    {
        $this->statusCode = $status;
        $content = View::render($template, $data);
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = $content;
        $this->send();
        return $this;
    }

    public function file(string $path, ?string $filename = null, ?string $mime = null): self
    {
        if (!is_file($path)) {
            return $this->json(['error' => 'File not found'], 404);
        }
        $this->headers['Content-Type'] = $mime ?? mime_content_type($path) ?: 'application/octet-stream';
        $this->headers['Content-Length'] = (string) filesize($path);
        if ($filename) {
            $this->headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        }
        $this->body = file_get_contents($path) ?: '';
        $this->send();
        return $this;
    }

    public function notFound(string $message = 'Not Found'): self
    {
        return $this->json(['error' => $message], 404);
    }

    public function unauthorized(string $message = 'Unauthorized'): self
    {
        return $this->json(['error' => $message], 401);
    }

    public function forbidden(string $message = 'Forbidden'): self
    {
        return $this->json(['error' => $message], 403);
    }

    public function badRequest(string $message = 'Bad Request', array $errors = []): self
    {
        return $this->json(['error' => $message, 'errors' => $errors], 400);
    }

    public function send(): void
    {
        if ($this->sent) return;
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }
        echo $this->body;
        $this->sent = true;
    }

    public function statusCode(): int { return $this->statusCode; }
    public function body(): string { return $this->body; }
}
