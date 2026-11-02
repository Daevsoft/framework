<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Pool\Pool;
use Ds\Pool\PoolManager;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class PoolTest extends TestCase
{
    public function testPoolDoesNotExceedConfiguredCapacity(): void
    {
        $disposed = [];
        $pool = new Pool(1, null, static function ($item) use (&$disposed): void {
            $disposed[] = $item;
        });

        $first = new stdClass();
        $second = new stdClass();
        $pool->release($first);
        $pool->release($second);

        self::assertSame(1, $pool->size());
        self::assertSame([$second], $disposed);
        self::assertSame($first, $pool->acquire());
        self::assertNull($pool->acquire());
    }

    public function testPoolRejectsDuplicateReleaseOfSameObject(): void
    {
        $pool = new Pool();
        $resource = new stdClass();
        $pool->release($resource);

        $this->expectException(LogicException::class);
        $pool->release($resource);
    }

    public function testPoolDisposesInvalidItems(): void
    {
        $disposed = [];
        $pool = new Pool(
            5,
            static fn (object $item): bool => $item->valid,
            static function ($item) use (&$disposed): void {
                $disposed[] = $item;
            }
        );

        $invalid = (object) ['valid' => false];
        $pool->release($invalid);

        self::assertSame(0, $pool->size());
        self::assertSame([$invalid], $disposed);
    }

    public function testPoolManagerBoundsNumberOfPools(): void
    {
        $manager = new PoolManager(1);
        $manager->get('database');

        $this->expectException(RuntimeException::class);
        $manager->get('redis');
    }
}
