<?php

declare(strict_types=1);

namespace Ds\Http;

use RuntimeException;

/**
 * Immutable metadata for one uploaded file. Storage is explicit and only
 * accepts files recognized by PHP as HTTP uploads.
 */
final class UploadedFile
{
    private readonly string $originalName;
    private readonly string $temporaryPath;
    private readonly string $clientMimeType;
    private readonly int $size;
    private readonly int $error;

    public function __construct(array $file)
    {
        $this->originalName = basename((string) ($file['name'] ?? ''));
        $this->temporaryPath = (string) ($file['tmp_name'] ?? '');
        $this->clientMimeType = (string) ($file['type'] ?? '');
        $this->size = max(0, (int) ($file['size'] ?? 0));
        $this->error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    }

    public function originalName(): string { return $this->originalName; }
    public function clientMimeType(): string { return $this->clientMimeType; }
    public function size(): int { return $this->size; }
    public function error(): int { return $this->error; }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK
            && $this->temporaryPath !== ''
            && is_uploaded_file($this->temporaryPath);
    }

    /**
     * Move the upload to an application-controlled directory and return its
     * generated basename. Executable/script extensions are rejected by default.
     */
    public function store(string $directory, ?string $filename = null): string
    {
        if (!$this->isValid()) {
            throw new RuntimeException('The uploaded file is invalid or is not an HTTP upload.');
        }

        $maximumBytes = max(1, (int) env('UPLOAD_MAX_BYTES', 10 * 1024 * 1024));
        if ($this->size > $maximumBytes) {
            throw new RuntimeException('The uploaded file exceeds the configured size limit.');
        }

        $extension = strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
        $blocked = ['php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'com', 'sh', 'apk', 'dll', 'so', 'js', 'html', 'htm', 'svg'];
        if ($extension !== '' && in_array($extension, $blocked, true)) {
            throw new RuntimeException('The uploaded file extension is not allowed.');
        }

        if ($filename === null || trim($filename) === '') {
            $filename = bin2hex(random_bytes(16));
        } else {
            $filename = pathinfo(basename($filename), PATHINFO_FILENAME);
            if (preg_match('/\A[A-Za-z0-9_-]{1,100}\z/D', $filename) !== 1) {
                throw new RuntimeException('The requested upload filename is invalid.');
            }
        }

        $storedName = $filename . ($extension === '' ? '' : '.' . $extension);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create upload storage directory.');
        }

        $root = realpath($directory);
        if ($root === false) {
            throw new RuntimeException('Unable to resolve upload storage directory.');
        }

        $target = $root . DIRECTORY_SEPARATOR . $storedName;
        if (file_exists($target)) {
            throw new RuntimeException('The requested upload filename already exists.');
        }

        if (!move_uploaded_file($this->temporaryPath, $target)) {
            throw new RuntimeException('Unable to move the uploaded file.');
        }

        return $storedName;
    }
}
