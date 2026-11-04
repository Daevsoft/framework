<?php

declare(strict_types=1);

namespace Ds\Foundations\Config;

use RuntimeException;

/**
 * v3 AppEnv adapter. The v4 bootstrap already loads .env into the process
 * environment, so this class populates missing values instead of generating
 * executable PHP cache files from untrusted text.
 */
class AppEnv
{
    private readonly string $envFile;

    public static function create(string $envFile): self
    {
        return new self($envFile);
    }

    public function __construct(string $envFile)
    {
        $this->envFile = $envFile;
        $this->generateConfig();
    }

    public function generateConfig(): array
    {
        if (!is_file($this->envFile) || !is_readable($this->envFile)) {
            throw new RuntimeException("Environment file is not readable: {$this->envFile}");
        }

        $values = [];
        foreach (file($this->envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key === '') {
                continue;
            }

            if (strlen($value) >= 2
                && (($value[0] === '"' && str_ends_with($value, '"'))
                    || ($value[0] === "'" && str_ends_with($value, "'")))) {
                $value = substr($value, 1, -1);
            }

            $values[$key] = $value;
            if (array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER) || getenv($key) !== false) {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }

        return $values;
    }
}
