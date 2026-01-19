<?php

declare(strict_types=1);

namespace Ds\Support;

use Ds\Http\Response;

class ResponseFactory
{
    /**
     * Create JSON response
     */
    public static function json(array $data, int $status = 200, array $headers = []): Response
    {
        $response = new Response($status, $headers, json_encode($data, JSON_UNESCAPED_SLASHES));
        $response->withHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Create HTML response
     */
    public static function html(string $content, int $status = 200, array $headers = []): Response
    {
        $response = new Response($status, $headers, $content);
        $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
        return $response;
    }

    /**
     * Create plain text response
     */
    public static function text(string $content, int $status = 200, array $headers = []): Response
    {
        $response = new Response($status, $headers, $content);
        $response->withHeader('Content-Type', 'text/plain');
        return $response;
    }

    /**
     * Create redirect response
     */
    public static function redirect(string $url, int $status = 302): Response
    {
        $response = new Response($status);
        $response->withHeader('Location', $url);
        return $response;
    }

    /**
     * Create file download response
     */
    public static function file(string $path, string $name = null): Response
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }

        $response = new Response(200, [
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
            'Content-Length' => (string)filesize($path),
            'Content-Type' => mime_content_type($path),
        ], file_get_contents($path));
        $name = $name ?? basename($path);

        return $response;
    }
}

if (!function_exists('response')) {
    /**
     * Helper function to get response factory
     */
    function response(): ResponseFactory {
        return new ResponseFactory();
    }
}
