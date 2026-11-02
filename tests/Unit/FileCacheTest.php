<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Support\FileCache;
use PHPUnit\Framework\TestCase;

final class FileCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ds-cache-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        foreach (glob($this->directory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public function testStoresReadsAndForgetsValues(): void
    {
        $cache = new FileCache($this->directory);
        self::assertTrue($cache->put('user:1', ['name' => 'Deva']));
        self::assertTrue($cache->has('user:1'));
        self::assertSame(['name' => 'Deva'], $cache->get('user:1'));
        self::assertTrue($cache->forget('user:1'));
        self::assertSame('missing', $cache->get('user:1', 'missing'));
    }

    public function testRememberOnlyResolvesMissingValues(): void
    {
        $cache = new FileCache($this->directory);
        $calls = 0;

        $first = $cache->remember('config', 60, static function () use (&$calls): string {
            $calls++;
            return 'cached';
        });
        $second = $cache->remember('config', 60, static function () use (&$calls): string {
            $calls++;
            return 'new';
        });

        self::assertSame('cached', $first);
        self::assertSame('cached', $second);
        self::assertSame(1, $calls);
    }

    public function testClearRemovesCacheEntries(): void
    {
        $cache = new FileCache($this->directory);
        $cache->put('a', 1);
        $cache->put('b', 2);

        self::assertSame(2, $cache->clear());
        self::assertFalse($cache->has('a'));
    }
}
