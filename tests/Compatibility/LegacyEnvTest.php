<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Foundations\Config\AppEnv;
use Ds\Foundations\Config\Env;
use PHPUnit\Framework\TestCase;

final class LegacyEnvTest extends TestCase
{
    public function testLegacyEnvAdapterLoadsOnlyMissingProcessValues(): void
    {
        $key = 'DS_COMPAT_ENV_' . strtoupper(bin2hex(random_bytes(4)));
        $existingKey = $key . '_EXISTING';
        $path = sys_get_temp_dir() . '/' . $key . '.env';
        $oldEnv = $_ENV[$key] ?? null;
        $oldServer = $_SERVER[$key] ?? null;
        $oldExistingEnv = $_ENV[$existingKey] ?? null;
        $oldExistingServer = $_SERVER[$existingKey] ?? null;
        $hadEnv = array_key_exists($key, $_ENV);
        $hadServer = array_key_exists($key, $_SERVER);
        $hadExistingEnv = array_key_exists($existingKey, $_ENV);
        $hadExistingServer = array_key_exists($existingKey, $_SERVER);

        unset($_ENV[$key], $_SERVER[$key]);
        $_ENV[$existingKey] = 'process-value';
        file_put_contents($path, "# comment\n{$key}=hello=world\n{$existingKey}=file-value\nQUOTED='two words'\n");

        try {
            AppEnv::create($path);
            self::assertSame('hello=world', Env::get($key));
            self::assertSame('process-value', Env::get($existingKey));
            self::assertSame('two words', Env::get('QUOTED'));
            self::assertSame('fallback', Env::get($key . '_MISSING', 'fallback'));
        } finally {
            @unlink($path);
            if ($hadEnv) $_ENV[$key] = $oldEnv; else unset($_ENV[$key]);
            if ($hadServer) $_SERVER[$key] = $oldServer; else unset($_SERVER[$key]);
            if ($hadExistingEnv) $_ENV[$existingKey] = $oldExistingEnv; else unset($_ENV[$existingKey]);
            if ($hadExistingServer) $_SERVER[$existingKey] = $oldExistingServer; else unset($_SERVER[$existingKey]);
            unset($_ENV['QUOTED'], $_SERVER['QUOTED']);
            putenv($key);
            putenv($existingKey);
            putenv('QUOTED');
        }
    }
}
