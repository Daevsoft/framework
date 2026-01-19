<?php

declare(strict_types=1);

namespace Ds\App;

/**
 * Application core: bertugas melakukan boot sekali dan mengelola lifecycle.
 */
class Application
{
    protected bool $booted = false;

    public function bootOnce(): void
    {
        if ($this->booted) {
            return;
        }

        $this->boot();
        $this->booted = true;
    }

    protected function boot(): void
    {
        // Registrasi provider, konfigurasi container, dsb.
    }

    public function isSwoole(): bool
    {
        return (string)($_ENV['APP_RUNTIME'] ?? 'fpm') === 'swoole';
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        // Lightweight, reflection-based instantiation for common cases
        if (! class_exists($abstract)) {
            return null;
        }
        $ref = new \ReflectionClass($abstract);
        if (! $ref->isInstantiable()) {
            return null;
        }
        $ctor = $ref->getConstructor();
        if (is_null($ctor)) {
            return new $abstract();
        }
        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $parameters)) {
                $args[] = $parameters[$name];
                continue;
            }
            $type = $param->getType();
            if ($type && ! $type->isBuiltin()) {
                $typeName = $type->getName();
                $args[] = $this->make($typeName);
            } elseif ($param->allowsNull()) {
                $args[] = null;
            } else {
                $args[] = null;
            }
        }
        return $ref->newInstanceArgs($args);
    }
}


