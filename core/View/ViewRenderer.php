<?php

declare(strict_types=1);

namespace Ds\View;

use RuntimeException;
use Throwable;

final class ViewRenderer
{
    private static ?self $default = null;
    private readonly PieCompiler $compiler;

    public function __construct(
        private readonly string $viewRoot,
        private readonly string $cacheRoot
    ) {
        $this->compiler = new PieCompiler($viewRoot, $cacheRoot);
    }

    public static function default(): self
    {
        if (self::$default === null) {
            $base = dirname(__DIR__, 2);
            self::$default = new self(
                $base . '/app/views',
                $base . '/app/storage/cache/views'
            );
        }

        return self::$default;
    }

    public function render(string $name, array $data = []): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, trim($name));
        if ($relative === '' || str_contains($relative, "\0")) {
            throw new RuntimeException('Invalid view name.');
        }

        $root = realpath($this->viewRoot);
        if ($root === false) {
            throw new RuntimeException("View directory does not exist: {$this->viewRoot}");
        }

        $phpFile = realpath($root . DIRECTORY_SEPARATOR . $relative . '.php');
        $pieFile = realpath($root . DIRECTORY_SEPARATOR . $relative . '.pie.php');
        $filename = $phpFile !== false ? $phpFile : $pieFile;

        if ($filename === false || !str_starts_with($filename, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException("View [{$name}] was not found.");
        }

        $compiled = str_ends_with($filename, '.pie.php')
            ? $this->compiler->compile($filename)
            : $filename;

        return self::includeView($compiled, $data);
    }

    public static function clearDefault(): void
    {
        self::$default = null;
    }

    private static function includeView(string $filename, array $data): string
    {
        return (static function (string $__filename, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();

            try {
                require $__filename;
                return (string) ob_get_clean();
            } catch (Throwable $exception) {
                ob_end_clean();
                throw $exception;
            }
        })($filename, $data);
    }
}
