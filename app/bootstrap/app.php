<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

// Load .env only for variables not already supplied by the process manager.
$envFile = $root . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '' || array_key_exists($key, $_ENV) || getenv($key) !== false) {
            continue;
        }

        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

$runtime = strtolower((string) env('APP_RUNTIME', 'swoole'));
if ($runtime === 'swoole') {
    require __DIR__ . '/swoole.php';
} elseif ($runtime === 'fpm') {
    require __DIR__ . '/fpm.php';
} else {
    throw new RuntimeException("Unsupported APP_RUNTIME '{$runtime}'. Use 'swoole' or 'fpm'.");
}
