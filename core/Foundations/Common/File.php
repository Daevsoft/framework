<?php

declare(strict_types=1);

namespace Ds\Foundations\Common;

/**
 * Lightweight v3 File facade. Operations use native filesystem calls and
 * exclusive locks instead of retaining open handles between chained calls.
 */
class File
{
    public const READ_ONLY = 'r';
    public const READ_PLUS = 'r+';
    public const WRITE_ONLY = 'w';
    public const WRITE_PLUS = 'w+';
    public const READ_WRITE = 'a+';
    public const CREATE_WRITE = 'x';
    public const CREATE_READ_WRITE = 'x+';

    private readonly string $path;
    private readonly string $filename;

    public function __construct(string $path, string $mode = self::READ_WRITE)
    {
        $this->path = $path;
        $this->filename = basename($path);
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new \RuntimeException("Unable to create directory: {$directory}");
        }
    }

    public function close(): self
    {
        return $this;
    }

    public function rewrite(string $contents): self
    {
        if (file_put_contents($this->path, $contents, LOCK_EX) === false) {
            throw new \RuntimeException("Unable to rewrite file: {$this->path}");
        }
        return $this;
    }

    public function append(string $contents): self
    {
        if (file_put_contents($this->path, $contents, FILE_APPEND | LOCK_EX) === false) {
            throw new \RuntimeException("Unable to append file: {$this->path}");
        }
        return $this;
    }

    public function create(string $contents = ''): self
    {
        if (!file_exists($this->path)) {
            $handle = @fopen($this->path, 'x');
            if ($handle === false) {
                throw new \RuntimeException("Unable to create file: {$this->path}");
            }
            fclose($handle);
        }

        if ($contents !== '') {
            $this->append($contents);
        }
        return $this;
    }

    public function delete(): bool
    {
        return !file_exists($this->path) || unlink($this->path);
    }

    public function getContent(): string
    {
        $contents = file_get_contents($this->path);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read file: {$this->path}");
        }
        return $contents;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
