<?php

declare(strict_types=1);

namespace Ds\Session;

use Ds\Http\Request;
use RuntimeException;

final class SessionManager
{
    private array $data = [];
    private bool $cookieChanged = false;
    private bool $destroyed = false;

    private function __construct(
        private string $sessionId,
        private readonly string $directory,
        private readonly string $cookieName,
        private readonly int $lifetime
    ) {
        if (preg_match('/\\A[A-Za-z0-9_-]+\\z/D', $cookieName) !== 1) {
            throw new \InvalidArgumentException('Session cookie name contains invalid characters.');
        }

        $this->load();
        $this->ageFlashData();
    }

    public static function start(
        Request $request,
        string $directory,
        string $cookieName = 'DSF_SESSID',
        int $lifetime = 7200
    ): self {
        $cookie = (string) $request->getHeader('cookie', '');
        $sessionId = null;

        if (preg_match('/(?:^|;\s*)' . preg_quote($cookieName, '/') . '=([a-f0-9]{64})(?:;|$)/', $cookie, $matches) === 1) {
            $sessionId = $matches[1];
        }

        if ($sessionId === null) {
            $sessionId = bin2hex(random_bytes(32));
        }

        $manager = new self($sessionId, $directory, $cookieName, max(60, $lifetime));
        if (!$manager->hasPersistedSession()) {
            $manager->cookieChanged = true;
        }

        return $manager;
    }

    public function id(): string { return $this->sessionId; }
    public function all(): array { return $this->data; }
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }
    public function has(string $key): bool { return array_key_exists($key, $this->data); }

    public function put(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function forget(string $key): self
    {
        unset($this->data[$key]);
        return $this;
    }

    public function regenerate(): void
    {
        $this->deleteFile();
        $this->sessionId = bin2hex(random_bytes(32));
        $this->cookieChanged = true;
        $this->destroyed = false;
    }

    public function destroy(): void
    {
        $this->data = [];
        $this->deleteFile();
        $this->destroyed = true;
        $this->cookieChanged = true;
    }

    public function flash(string $key, mixed $value): void
    {
        $next = $this->data['_flash_next'] ?? [];
        $next[$key] = $value;
        $this->data['_flash_next'] = $next;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $this->data['_flash_current'][$key] ?? $default;
    }

    public function hasFlash(string $key): bool
    {
        return array_key_exists($key, $this->data['_flash_current'] ?? []);
    }

    public function setOldInput(array $input): void
    {
        $this->flash('_old_input', $input);
    }

    public function old(string $key, mixed $default = null): mixed
    {
        $input = $this->getFlash('_old_input', []);
        return is_array($input) ? ($input[$key] ?? $default) : $default;
    }

    public function save(): void
    {
        if ($this->destroyed) {
            $this->deleteFile();
            return;
        }

        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Unable to create session storage directory.');
        }

        $payload = json_encode([
            'expires_at' => time() + $this->lifetime,
            'data' => $this->data,
        ], JSON_THROW_ON_ERROR);

        $file = $this->filePath();
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open session storage file.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock session storage file.');
            }

            ftruncate($handle, 0);
            rewind($handle);
            if (fwrite($handle, $payload) === false) {
                throw new RuntimeException('Unable to persist session data.');
            }
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    public function cookieHeader(): ?string
    {
        if (!$this->cookieChanged) {
            return null;
        }

        $secure = (bool) env('SESSION_SECURE', false);
        if ($this->destroyed) {
            return $this->cookieName . '=; Path=/; Max-Age=0; HttpOnly; SameSite=Lax'
                . ($secure ? '; Secure' : '');
        }

        return $this->cookieName . '=' . $this->sessionId
            . '; Path=/; Max-Age=' . $this->lifetime
            . '; HttpOnly; SameSite=Lax'
            . ($secure ? '; Secure' : '');
    }

    private function load(): void
    {
        $file = $this->filePath();
        if (!is_file($file)) {
            return;
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return;
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                return;
            }
            $raw = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if ($raw === false) {
            return;
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->deleteFile();
            return;
        }

        if (!is_array($payload) || !is_array($payload['data'] ?? null)) {
            $this->deleteFile();
            return;
        }

        if (!is_int($payload['expires_at'] ?? null) || $payload['expires_at'] <= time()) {
            $this->deleteFile();
            $this->sessionId = bin2hex(random_bytes(32));
            $this->cookieChanged = true;
            return;
        }

        $this->data = $payload['data'];
    }

    private function ageFlashData(): void
    {
        $this->data['_flash_current'] = $this->data['_flash_next'] ?? [];
        unset($this->data['_flash_next']);
    }

    private function hasPersistedSession(): bool
    {
        return is_file($this->filePath());
    }

    private function filePath(): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . hash('sha256', $this->sessionId)
            . '.json';
    }

    private function deleteFile(): void
    {
        $file = $this->filePath();
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
