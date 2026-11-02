<?php

declare(strict_types=1);

namespace Ds\Support;

use Ds\Http\Response;
use RuntimeException;

final class ResponseFactory
{
    public static function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers),
            json_encode($data, JSON_THROW_ON_ERROR)
        );
    }

    public static function html(string $content, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers),
            $content
        );
    }

    public static function text(string $content, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'text/plain; charset=utf-8'], $headers),
            $content
        );
    }

    public static function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    public static function file(string $path, ?string $name = null): Response
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("File not found or unreadable: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Unable to read file: {$path}");
        }

        $name ??= basename($path);
        return new Response(200, [
            'Content-Disposition' => 'attachment; filename="' . addcslashes($name, '"\\') . '"',
            'Content-Length' => (string) strlen($content),
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
        ], $content);
    }
}
