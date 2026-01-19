<?php

declare(strict_types=1);

namespace Ds\Http;

class Request
{
    public string $path = '/';
    public string $method = 'GET';
    public array $query = [];

    // Flattened data from GET/POST/JSON body for convenient access
    protected array $data = [];

    public function __construct()
    {
        $this->loadInputs();
    }

    public function loadInputs(): void
    {
        $this->data = [];
        // Merge GET first, then POST overrides, then JSON body overrides
        if (!empty($_GET)) {
            $this->deepMerge($this->data, $_GET);
        }
        if (!empty($_POST)) {
            $this->deepMerge($this->data, $_POST);
        }
        $body = @file_get_contents('php://input');
        if ($body !== false && $body !== '') {
            $json = json_decode($body, true);
            if (is_array($json)) {
                $this->deepMerge($this->data, $json);
            }
        }
    }

    private function deepMerge(array &$target, array $source): void
    {
        foreach ($source as $k => $v) {
            if (is_array($v) && isset($target[$k]) && is_array($target[$k])) {
                $this->deepMerge($target[$k], $v);
            } else {
                $target[$k] = $v;
            }
        }
    }

    public static function capture(): self
    {
        $req = new self();
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $req->path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $req->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $qs = parse_url($uri, PHP_URL_QUERY);
        $req->query = [];
        if ($qs) {
            parse_str($qs, $req->query);
        }
        // Ensure data is loaded for capture as well
        $req->loadInputs();
        return $req;
    }

    // Convenience: build from Swoole request
    public static function fromSwoole($swReq): self
    {
        $r = new self();
        $path = $swReq->server['request_uri'] ?? '/';
        $r->path = parse_url($path, PHP_URL_PATH) ?: '/';
        $r->method = $swReq->server['request_method'] ?? 'GET';
        $qs = parse_url($path, PHP_URL_QUERY);
        $r->query = [];
        if ($qs) parse_str($qs, $r->query);
        // Merge any data from Swoole body as well if needed
        $r->loadInputs();
        return $r;
    }

    // Dynamic access: $request->username reads from inputs
    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    // Return a specific input value or default
    public function input(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    // Return all aggregated inputs
    public function all(): array
    {
        return $this->data;
    }
}
