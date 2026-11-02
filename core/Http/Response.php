<?php

declare(strict_types=1);

namespace Ds\Http;

final class Response
{
    private int $status;
    private array $headers;
    private string $body;

    /**
     * The second argument accepts either headers or a body string for
     * compatibility with the original new Response($status, $body) examples.
     */
    public function __construct(int $status = 200, array|string $headers = [], string $body = '')
    {
        $this->status = $status;
        if (is_string($headers)) {
            $this->headers = [];
            $this->body = $headers;
        } else {
            $this->headers = $headers;
            $this->body = $body;
        }
    }

    public function status(): int { return $this->status; }
    public function headers(): array { return $this->headers; }
    public function body(): string { return $this->body; }

    public function withStatus(int $status): self
    {
        $new = clone $this;
        $new->status = $status;
        return $new;
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers[$name] = $value;
        return $new;
    }

    public function withBody(string $body): self
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public static function text(string $text, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/plain; charset=utf-8'], $text);
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $encoded = json_encode($data, JSON_THROW_ON_ERROR);
        return new self($status, ['Content-Type' => 'application/json; charset=utf-8'], $encoded);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self($status, ['Location' => $location]);
    }

    public static function ok(): self
    {
        return new self(200, [], 'ok');
    }

    public function send(): void
    {
        (new ResponseEmitter())->emit($this);
    }
}
