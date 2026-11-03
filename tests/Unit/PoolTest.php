<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Pool\Pool;
use Ds\Pool\PoolManager;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use WeakReference;

final class PoolTest extends TestCase
{

    public function testPoolDestructorDisposesPooledResources(): void
    {
        $disposed = [];
        $resource = new stdClass();
        $pool = new Pool(2, null, static function ($item) use (&$disposed): void {
            $disposed[] = $item;
        });
        $pool->release($resource);

        unset($pool);

        self::assertSame([$resource], $disposed);
    }

    public function testPoolManagerDestructorDisposesManagedResources(): void
    {
        $disposed = [];
        $resource = new stdClass();
        $manager = new PoolManager();
        $manager->get('database', 2, null, static function ($item) use (&$disposed): void {
            $disposed[] = $item;
        })->release($resource);

        unset($manager);

        self::assertSame([$resource], $disposed);
    }


    public function testPoolRejectsReentrantReleaseDuringClear(): void
    {
        $poolReference = null;
        $resource = new stdClass();
        $pool = new Pool(2, null, static function ($item) use (&$poolReference): void {
            $poolReference?->get()?->release(new stdClass());
        });
        $poolReference = WeakReference::create($pool);
        $pool->release($resource);

        try {
            $pool->clear();
            self::fail('Reentrant release should be rejected during clear.');
        } catch (LogicException $exception) {
            self::assertStringContainsString('being cleared', $exception->getMessage());
        }

        self::assertSame(0, $pool->size());
    }

    public function testPoolManagerRejectsPoolCreationDuringClear(): void
    {
        $manager = new PoolManager();
        $managerReference = WeakReference::create($manager);
        $manager->get('primary', 2, null, static function () use ($managerReference): void {
            $managerReference->get()?->get('created-during-clear');
        })->release(new stdClass());

        try {
            $manager->clear();
            self::fail('Pool creation should be rejected while manager is clearing.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('being cleared', $exception->getMessage());
        }

        self::assertSame(0, $manager->count());
        $manager->get('after-clear');
        self::assertSame(1, $manager->count());
    }

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
