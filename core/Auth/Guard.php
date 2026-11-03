<?php

declare(strict_types=1);

namespace Ds\Auth;

use Ds\Http\Request;
use Throwable;

final class Guard
{
    private ?Identity $identity = null;

    public function __construct(private readonly mixed $resolver = null) {}

    public function check(?Request $request = null): bool
    {
        $this->identity = null;
        if ($request === null || !is_callable($this->resolver)) return false;

        $header = trim((string) $request->getHeader('authorization', ''));
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) return false;
        $token = trim($matches[1]);
        if ($token === '') return false;

        try {
            $resolved = ($this->resolver)($token, $request);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            return false;
        }

        if ($resolved instanceof Identity) {
            $this->identity = $resolved;
            return true;
        }

        if (is_array($resolved) && isset($resolved['id'])) {
            $roles = $resolved['roles'] ?? [];
            if (!is_array($roles)) return false;
            $this->identity = new Identity((string) $resolved['id'], $roles);
            return true;
        }

        return false;
    }

    public function user(): ?Identity { return $this->identity; }
    public function id(): ?string { return $this->identity?->id; }
}
