<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Ds\Console\Command;
use InvalidArgumentException;
use RuntimeException;

abstract class GeneratorCommand extends Command
{
    protected function names(): array
    {
        return array_values(array_filter(
            $this->parameters,
            static fn (string $value): bool => !str_starts_with($value, '--')
        ));
    }

    protected function segments(string $name): array
    {
        $segments = explode('/', trim($name, '/'));
        foreach ($segments as $segment) {
            if (preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/D', $segment) !== 1) {
                throw new InvalidArgumentException("Invalid generated name [{$name}].");
            }
        }

        return $segments;
    }

    protected function write(string $relativePath, string $content): bool
    {
        $root = base_path();
        $target = $root . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
        $directory = dirname($target);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create directory {$directory}.");
        }

        if (file_exists($target)) {
            $this->error("Already exists: {$relativePath}");
            return false;
        }

        if (file_put_contents($target, $content, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write {$relativePath}.");
        }

        $this->info("Created: {$relativePath}");
        return true;
    }

    protected function className(array $segments, string $suffix = ''): string
    {
        $name = end($segments);
        if ($suffix !== '' && !str_ends_with($name, $suffix)) {
            $name .= $suffix;
        }
        return $name;
    }

    protected function namespaceFor(string $base, array $segments): string
    {
        array_pop($segments);
        return $segments === [] ? $base : $base . '\\' . implode('\\', $segments);
    }

    protected function snake(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }
}
