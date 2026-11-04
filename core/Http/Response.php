<?php

declare(strict_types=1);

namespace Ds\Http;

use Ds\Context\Context;
use InvalidArgumentException;

final class Response
{
    private int $status;
    private array $headers;
    private string $body;
    private const LEGACY_CONTEXT_KEY = 'response.legacy';

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

    /** Request-scoped compatibility for the v3 static Response::header() API. */
    public static function header(string $header, bool $replace = true, ?int $code = null): void
    {
        $parts = explode(':', $header, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Response header must contain a name and value separated by a colon.');
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);
        if (preg_match('/\\A[!#$%&\'*+.^_|~0-9A-Za-z-]+\\z/D', $name) !== 1
            || preg_match('/[\\r\\n]/', $value) === 1) {
            throw new InvalidArgumentException('Invalid response header name or value.');
        }
        if ($code !== null && ($code < 100 || $code > 599)) {
            throw new InvalidArgumentException('HTTP status code must be between 100 and 599.');
        }

        $context = Context::default();
        $state = $context->get(self::LEGACY_CONTEXT_KEY, ['status' => null, 'headers' => []]);
        if ($code !== null) {
            $state['status'] = $code;
        }
        if ($replace || !array_key_exists($name, $state['headers'])) {
            $state['headers'][$name] = $value;
        }
        $context->set(self::LEGACY_CONTEXT_KEY, $state);
    }

    public static function reset(): void
    {
        Context::default()->remove(self::LEGACY_CONTEXT_KEY);
    }

    /** Merge legacy static header/status calls into the immutable response. */
    public static function consumeLegacyOverrides(self $response): self
    {
        $state = self::takeLegacyState();
        if ($state['status'] !== null) {
            $response = $response->withStatus((int) $state['status']);
        }
        foreach ($state['headers'] as $name => $value) {
            $response = $response->withHeader((string) $name, (string) $value);
        }
        return $response;
    }

    public static function applyToSwoole(object $response): void
    {
        $state = self::takeLegacyState();
        $response->status((int) ($state['status'] ?? 200));
        foreach ($state['headers'] as $name => $value) {
            $response->header((string) $name, (string) $value);
        }
    }

    public static function applyToPhp(): void
    {
        $state = self::takeLegacyState();
        http_response_code((int) ($state['status'] ?? 200));
        foreach ($state['headers'] as $name => $value) {
            header((string) $name . ': ' . (string) $value);
        }
    }

    private static function takeLegacyState(): array
    {
        $context = Context::default();
        $state = $context->get(self::LEGACY_CONTEXT_KEY, ['status' => null, 'headers' => []]);
        $context->remove(self::LEGACY_CONTEXT_KEY);
        return is_array($state) ? $state : ['status' => null, 'headers' => []];
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
