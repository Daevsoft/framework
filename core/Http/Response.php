<?php

declare(strict_types=1);

namespace Ds\Http;

final class Response
{
    private int $status;
    private array $headers = [];
    private string $body = '';

    public function __construct(int $status = 200, array $headers = [], string $body = '')
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function status(): int { return $this->status; }
    public function headers(): array { return $this->headers; }
    public function body(): string { return $this->body; }

    // Immutability helpers
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

    // Helpers
    public static function text(string $text): self
    {
        return new self(200, ['Content-Type' => 'text/plain'], $text);
    }

    public static function json(mixed $data): self
    {
        $encoded = json_encode($data, JSON_THROW_ON_ERROR);
        return new self(200, ['Content-Type' => 'application/json'], $encoded);
    }

    public static function ok(): self
    {
        return new self(200, [], 'ok');
    }

    public function send(): void
    {
        $emitter = new ResponseEmitter();
        $emitter->emit($this);
    }
}

