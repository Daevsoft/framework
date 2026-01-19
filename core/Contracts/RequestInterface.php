<?php

namespace Core\Contracts;

interface RequestInterface
{
    public function getMethod(): string;
    public function getPath(): string;
    public function getUri(): string;
    public function getHeaders(): array;
    public function getHeader(string $name, $default = null);
    public function getQuery(string $key = null, $default = null);
    public function getBody();
    public function getParsedBody();
    public function getInput(string $key = null, $default = null);
    public function all(): array;
    public function has(string $key): bool;
    public function only(array $keys): array;
    public function except(array $keys): array;
}
{
}

