<?php

declare(strict_types=1);

namespace Ds\Http;

use Ds\Context\Context;
use Ds\Validation\Validator;
/**
 * Immutable-per-request HTTP data. Never reads PHP superglobals from a
 * Swoole request; all request state is copied from the server request object.
 */
final class Request
{
    public string $path = '/';
    public string $method = 'GET';
    public array $query = [];

    protected array $data = [];
    protected array $headers = [];
    protected array $files = [];
    protected string $body = '';
    private string $scheme;

    public function __construct(
        array $data = [],
        array $headers = [],
        string $method = 'GET',
        string $path = '/',
        string $scheme = 'http',
        array $files = []
    ) {
        $this->data = $data;
        $this->headers = self::normalizeHeaders($headers);
        $this->method = strtoupper($method);
        $this->path = self::normalizePath($path);
        $this->scheme = strtolower($scheme) === 'https' ? 'https' : 'http';
        $this->files = self::normalizeUploadedFiles($files);
        $this->query = [];
    }

    public static function capture(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = [];
        parse_str((string) (parse_url($uri, PHP_URL_QUERY) ?? ''), $query);

        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $body = file_get_contents('php://input');
        $scheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https'
            || (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
                ? 'https'
                : 'http';
        $request = new self([], $headers, $_SERVER['REQUEST_METHOD'] ?? 'GET', $path, $scheme, $_FILES ?? []);
        $request->query = $query;
        $request->body = $body === false ? '' : $body;
        $request->mergeInput($_GET ?? []);
        $request->mergeInput($_POST ?? []);
        $request->mergeParsedBody($request->body);

        return $request;
    }

    public static function fromSwoole(object $swRequest): self
    {
        $server = $swRequest->server ?? [];
        $uri = (string) ($server['request_uri'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = [];
        parse_str((string) (parse_url($uri, PHP_URL_QUERY) ?? ''), $query);

        $headers = (array) ($swRequest->header ?? []);
        $scheme = strtolower((string) ($server['https'] ?? '')) === 'on'
            || (int) ($server['server_port'] ?? 0) === 443
                ? 'https'
                : 'http';
        $request = new self(
            [],
            $headers,
            (string) ($server['request_method'] ?? 'GET'),
            $path,
            $scheme,
            (array) ($swRequest->files ?? [])
        );
        $request->query = $query;
        $request->body = method_exists($swRequest, 'rawContent')
            ? (string) ($swRequest->rawContent() ?: '')
            : '';

        $request->mergeInput((array) ($swRequest->get ?? []));
        $request->mergeInput((array) ($swRequest->post ?? []));
        $request->mergeParsedBody($request->body);

        return $request;
    }

    /**
     * Store the current local URI in session history for the legacy back/referrer API.
     * Only the latest two local paths are retained; no request objects or headers
     * are stored in the long-lived worker.
     */
    public static function trackRoute(?string $uri = null): void
    {
        $session = self::routeHistorySession();
        $request = Context::default()->get('request');
        $uri ??= $request instanceof self ? $request->getUri() : null;

        if (!is_string($uri) || !self::isSafeLocalUri($uri)) {
            throw new \InvalidArgumentException('Route history requires a local absolute-path URI.');
        }

        $history = self::normalizeRouteHistory($session->get('track_route', []));
        if (($history[count($history) - 1] ?? null) !== $uri) {
            $history[] = $uri;
        }

        $session->put('track_route', array_slice($history, -2));
    }

    /** Return and consume the latest local route history entries. */
    public static function getTrackRoute(): array
    {
        $session = self::routeHistorySession();
        $history = self::normalizeRouteHistory($session->get('track_route', []));
        $session->forget('track_route');

        return $history;
    }

    /** Return the previous local URI, or the current URI when no history exists. */
    public static function getReferrer(): string
    {
        $history = self::getTrackRoute();
        if (count($history) >= 2) {
            return $history[count($history) - 2];
        }
        if ($history !== []) {
            return $history[0];
        }

        $request = Context::default()->get('request');
        return $request instanceof self ? $request->getUri() : '/';
    }

    /**
     * Return a redirect response instead of emitting headers directly.
     * Controllers should return this response to the HTTP kernel.
     */
    public static function getBack(): Response
    {
        return Response::redirect(self::getReferrer());
    }

    private static function routeHistorySession(): \Ds\Session\SessionManager
    {
        $session = Context::default()->get('session');
        if (!$session instanceof \Ds\Session\SessionManager) {
            throw new \RuntimeException('Route history requires SessionMiddleware.');
        }

        return $session;
    }

    private static function normalizeRouteHistory(mixed $history): array
    {
        if (is_string($history)) {
            // Read the v3 serialized representation without allowing object creation.
            try {
                $decoded = @unserialize($history, ['allowed_classes' => false, 'max_depth' => 8]);
            } catch (\Throwable) {
                return [];
            }
            $history = $decoded;
        }

        if (!is_array($history)) {
            return [];
        }

        $routes = [];
        foreach ($history as $uri) {
            if (is_string($uri) && self::isSafeLocalUri($uri)) {
                $routes[] = $uri;
            }
        }

        return array_slice($routes, -2);
    }

    private static function isSafeLocalUri(string $uri): bool
    {
        return $uri !== ''
            && str_starts_with($uri, '/')
            && !str_starts_with($uri, '//')
            && preg_match('/[\x00-\x1F\x7F]/', $uri) !== 1
            && strpbrk($uri, "\r\n") === false;
    }

    public function getMethod(): string { return $this->method; }
    public function getPath(): string { return $this->path; }
    public function getScheme(): string { return $this->scheme; }

    /** Return one uploaded file without moving it into application storage. */
    public function file(string $field): ?UploadedFile
    {
        $files = $this->files($field);
        return $files[0] ?? null;
    }

    /** @return array<int|string, UploadedFile> */
    public function files(string $field): array
    {
        $files = $this->files[$field] ?? [];
        if ($files instanceof UploadedFile) {
            return [$files];
        }
        return is_array($files)
            ? array_filter($files, static fn (mixed $file): bool => $file instanceof UploadedFile)
            : [];
    }

    public function allFiles(): array
    {
        return $this->files;
    }
    public function getUri(): string
    {
        return $this->path . ($this->query === [] ? '' : '?' . http_build_query($this->query));
    }
    public function getHeaders(): array { return $this->headers; }
    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }
    public function getQuery(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }
    public function getBody(): string { return $this->body; }
    public function getParsedBody(): array
    {
        $parsed = json_decode($this->body, true);
        return is_array($parsed) ? $parsed : [];
    }

    /** v3-compatible JSON body accessor; returns an object by default. */
    public function json(bool $associative = false): mixed
    {
        return $this->body === '' ? null : json_decode($this->body, $associative);
    }

    /** Add request-local attributes for middleware-to-controller data passing. */
    public function add(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function old(string $key, mixed $default = null): mixed
    {
        // v3 Request::setOld() stores each field under "_old_{key}", not
        // inside the aggregate "_old_input" payload used by old().
        return flash('_old_' . $key, $default);
    }

    public function setOld(string $key, mixed $value): void
    {
        set_flash('_old_' . $key, $value);
    }
    public function getInput(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->data : ($this->data[$key] ?? $default);
    }
    public function input(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }
    public function all(): array { return $this->data; }
    public function has(string $key): bool { return array_key_exists($key, $this->data); }
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }
    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    public function validate(array $rules, array $messages = []): array
    {
        return Validator::make($this->data, $rules, $messages)->validate();
    }

    public function __get(string $name): mixed { return $this->data[$name] ?? null; }
    public function __isset(string $name): bool { return isset($this->data[$name]); }

    protected function mergeParsedBody(string $body): void
    {
        if ($body === '') {
            return;
        }

        $contentType = strtolower((string) ($this->headers['content-type'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $this->mergeInput($decoded);
            }
            return;
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $parsed = [];
            parse_str($body, $parsed);
            $this->mergeInput($parsed);
        }
    }

    protected function mergeInput(array $source): void
    {
        foreach ($source as $key => $value) {
            if (is_array($value) && isset($this->data[$key]) && is_array($this->data[$key])) {
                $this->data[$key] = array_replace_recursive($this->data[$key], $value);
            } else {
                $this->data[$key] = $value;
            }
        }
    }

    private static function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $field => $file) {
            if (!is_array($file) || !array_key_exists('name', $file)) {
                continue;
            }

            if (is_array($file['name'])) {
                foreach ($file['name'] as $key => $name) {
                    $entry = [];
                    foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $attribute) {
                        $value = $file[$attribute] ?? null;
                        $entry[$attribute] = is_array($value) ? ($value[$key] ?? null) : $value;
                    }
                    $normalized[$field][$key] = new UploadedFile($entry);
                }
                continue;
            }

            $normalized[$field] = new UploadedFile($file);
        }

        return $normalized;
    }

    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower((string) $name)] = is_array($value)
                ? implode(', ', $value)
                : (string) $value;
        }
        return $normalized;
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
