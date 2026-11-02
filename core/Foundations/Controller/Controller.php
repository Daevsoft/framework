<?php

declare(strict_types=1);

namespace Ds\Foundations\Controller;

use Ds\Http\Response;

/**
 * Compatibility controller base for v3 applications.
 *
 * New controllers may remain plain classes; this base only supplies common
 * response helpers and does not add per-request framework work.
 */
abstract class Controller
{
    protected function view(string $name, array $data = []): Response
    {
        return Response::html(\view($name, $data));
    }

    protected function redirect(string $route, int $status = 302): Response
    {
        return Response::redirect($route, $status);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}
