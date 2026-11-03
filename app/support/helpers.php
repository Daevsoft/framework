<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        return match (strtolower(trim($value))) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => trim($value, " \"'")
        };
    }
}

if (!function_exists('response')) {
    /**
     * response() returns the response factory; response($body, ...) creates
     * a concrete response. Both fluent and direct usage remain lightweight.
     */
    function response(
        mixed $content = null,
        int $status = 200,
        array $headers = []
    ): \Ds\Http\Response|\Ds\Support\ResponseFactory {
        if (func_num_args() === 0) {
            return new \Ds\Support\ResponseFactory();
        }

        return new \Ds\Http\Response($status, $headers, (string) $content);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return \Ds\Config\ConfigRepository::get($key, $default);
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        $base = dirname(__DIR__);
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2) . '/public';
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}





if (!function_exists('session_manager')) {
    function session_manager(): \Ds\Session\SessionManager
    {
        $manager = \Ds\Context\Context::default()->get('session');
        if (!$manager instanceof \Ds\Session\SessionManager) {
            throw new \RuntimeException('Session access requires SessionMiddleware.');
        }
        return $manager;
    }
}

if (!function_exists('session')) {
    function session(string|array|null $key = null, mixed $default = null): mixed
    {
        $manager = \Ds\Context\Context::default()->get('session');
        if ($manager === null) {
            throw new \RuntimeException('Session access requires SessionMiddleware.');
        }

        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $manager->put((string) $name, $value);
            }
            return null;
        }

        return $key === null ? $manager->all() : $manager->get($key, $default);
    }
}

if (!function_exists('unsession')) {
    function unsession(string $key): void
    {
        $manager = \Ds\Context\Context::default()->get('session');
        if ($manager === null) {
            throw new \RuntimeException('Session access requires SessionMiddleware.');
        }
        $manager->forget($key);
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $key, mixed $value): void
    {
        $manager = \Ds\Context\Context::default()->get('session');
        if ($manager === null) {
            throw new \RuntimeException('Flash data requires SessionMiddleware.');
        }
        $manager->flash($key, $value);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $default = null): mixed
    {
        $manager = \Ds\Context\Context::default()->get('session');
        return $manager?->getFlash($key, $default) ?? $default;
    }
}

if (!function_exists('is_flash')) {
    function is_flash(string $key): bool
    {
        $manager = \Ds\Context\Context::default()->get('session');
        return $manager?->hasFlash($key) ?? false;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        $manager = \Ds\Context\Context::default()->get('session');
        return $manager?->old($key, $default) ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \Ds\Security\Csrf::token();
    }
}

if (!function_exists('db')) {
    function db(): \Ds\Database\DatabaseManager
    {
        return \Ds\Database\DatabaseManager::default();
    }
}

if (!function_exists('view')) {
    /**
     * Render a PHP or PieTemplate view to a string.
     *
     * Return the result from a controller so the HTTP dispatcher can emit it:
     * return response()->html(view('pages.home', ['title' => 'Home']));
     */
    function view(string $name, array $data = []): string
    {
        return \Ds\View\ViewRenderer::default()->render($name, $data);
    }
}

if (!function_exists('asset')) {
    /**
     * Resolve a public asset URL. ASSET_URL may point to a CDN or a
     * reverse-proxy asset prefix; without it, assets are served from web root.
     */
    function asset(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(?:https?:)?//#i', $path) === 1) {
            return $path;
        }

        $base = rtrim((string) env('ASSET_URL', ''), '/');
        $path = '/' . ltrim($path, '/');

        return $base . $path;
    }
}

if (!function_exists('css_source')) {
    function css_source(string $source): string
    {
        $source = trim($source);
        if ($source === '') {
            return '';
        }

        $path = str_starts_with($source, '/') ? $source : '/assets/css/' . ltrim($source, '/');
        if (pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION) === '') {
            $path .= '.css';
        }

        return '<link rel="stylesheet" href="' . htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}

if (!function_exists('js_source')) {
    function js_source(string $source): string
    {
        $source = trim($source);
        if ($source === '') {
            return '';
        }

        $path = str_starts_with($source, '/') ? $source : '/assets/js/' . ltrim($source, '/');
        if (pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION) === '') {
            $path .= '.js';
        }

        return '<script src="' . htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"></script>';
    }
}

if (!function_exists('collect')) {
    /**
     * Create a v3-compatible collection. New code may use Ds\Support\Collection::make().
     */
    function collect(iterable $items = []): \Ds\Foundations\Common\Collection
    {
        return new \Ds\Foundations\Common\Collection(
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$args): never
    {
        foreach ($args as $arg) {
            var_dump($arg);
        }
        exit(1);
    }
}

if (!function_exists('dump')) {
    function dump(mixed ...$args): void
    {
        foreach ($args as $arg) {
            var_dump($arg);
        }
    }
}
