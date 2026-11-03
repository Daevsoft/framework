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


    public function testPoolManagerRejectsRecreationDuringForget(): void
    {
        $manager = new PoolManager();
        $managerReference = WeakReference::create($manager);
        $manager->get('cache', 2, null, static function () use ($managerReference): void {
            $managerReference->get()?->get('cache');
        })->release(new stdClass());

        try {
            $manager->forget('cache');
            self::fail('Pool recreation should be rejected while the named pool is being forgotten.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('being forgotten', $exception->getMessage());
        }

        self::assertSame(0, $manager->count());
        $manager->get('cache');
        self::assertSame(1, $manager->count());
    }


    public function testPoolManagerAndPoolRejectReentrantOperationsWhileDisposerYields(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !class_exists(\Swoole\Coroutine\Scheduler::class)) {
            self::markTestSkipped('Swoole coroutine scheduler is unavailable.');
        }

        $poolManager = new PoolManager();
        $pool = $poolManager->get('yielding', 2, null, static function (): void {
            \Swoole\Coroutine::sleep(0.03);
        });
        $pool->release(new stdClass());
        $managerRejected = false;
        $poolRejected = false;

        $runner = static function (callable $callback): void {
            if (method_exists(\Swoole\Coroutine::class, 'run')) {
                \Swoole\Coroutine::run($callback);
                return;
            }

            $scheduler = new \Swoole\Coroutine\Scheduler();
            $scheduler->add($callback);
            $scheduler->start();
        };

        $runner(static function () use ($poolManager, $pool, &$managerRejected, &$poolRejected): void {
            \Swoole\Coroutine::create(static function () use ($poolManager): void {
                $poolManager->clear();
            });

            \Swoole\Coroutine::create(static function () use ($poolManager, $pool, &$managerRejected, &$poolRejected): void {
                \Swoole\Coroutine::sleep(0.005);
                try {
                    $poolManager->get('late');
                } catch (RuntimeException) {
                    $managerRejected = true;
                }

                try {
                    $pool->release(new stdClass());
                } catch (LogicException) {
                    $poolRejected = true;
                }
            });
        });

        self::assertTrue($managerRejected);
        self::assertTrue($poolRejected);
        self::assertSame(0, $poolManager->count());
        self::assertSame(0, $pool->size());
    }

    public function testPoolManagerBlocksSameNameRecreationWhileForgetDisposerYields(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !class_exists(\Swoole\Coroutine\Scheduler::class)) {
            self::markTestSkipped('Swoole coroutine scheduler is unavailable.');
        }

        $manager = new PoolManager();
        $manager->get('cache', 2, null, static function (): void {
            \Swoole\Coroutine::sleep(0.03);
        })->release(new stdClass());
        $recreationRejected = false;

        $runner = static function (callable $callback): void {
            if (method_exists(\Swoole\Coroutine::class, 'run')) {
                \Swoole\Coroutine::run($callback);
                return;
            }

            $scheduler = new \Swoole\Coroutine\Scheduler();
            $scheduler->add($callback);
            $scheduler->start();
        };

        $runner(static function () use ($manager, &$recreationRejected): void {
            \Swoole\Coroutine::create(static function () use ($manager): void {
                $manager->forget('cache');
            });

            \Swoole\Coroutine::create(static function () use ($manager, &$recreationRejected): void {
                \Swoole\Coroutine::sleep(0.005);
                try {
                    $manager->get('cache');
                } catch (RuntimeException) {
                    $recreationRejected = true;
                }
            });
        });

        self::assertTrue($recreationRejected);
        self::assertSame(0, $manager->count());
        $manager->get('cache');
        self::assertSame(1, $manager->count());
    }


    public function testConcurrentDuplicateReleaseIsRejectedWhileValidatorYields(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !class_exists(\Swoole\Coroutine\Scheduler::class)) {
            self::markTestSkipped('Swoole coroutine scheduler is unavailable.');
        }

        $pool = new Pool(2, static function (): bool {
            \Swoole\Coroutine::sleep(0.03);
            return true;
        });
        $resource = new stdClass();
        $duplicateRejected = false;
        $runner = static function (callable $callback): void {
            if (method_exists(\Swoole\Coroutine::class, 'run')) {
                \Swoole\Coroutine::run($callback);
                return;
            }
            $scheduler = new \Swoole\Coroutine\Scheduler();
            $scheduler->add($callback);
            $scheduler->start();
        };

        $runner(static function () use ($pool, $resource, &$duplicateRejected): void {
            \Swoole\Coroutine::create(static function () use ($pool, $resource): void {
                $pool->release($resource);
            });
            \Swoole\Coroutine::create(static function () use ($pool, $resource, &$duplicateRejected): void {
                \Swoole\Coroutine::sleep(0.005);
                try {
                    $pool->release($resource);
                } catch (LogicException) {
                    $duplicateRejected = true;
                }
            });
        });

        self::assertTrue($duplicateRejected);
        self::assertSame(1, $pool->size());
    }

    public function testClearInvalidatesAReleaseSuspendedInAValidator(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !class_exists(\Swoole\Coroutine\Scheduler::class)) {
            self::markTestSkipped('Swoole coroutine scheduler is unavailable.');
        }

        $pool = new Pool(2, static function (): bool {
            \Swoole\Coroutine::sleep(0.03);
            return true;
        });
        $resource = new stdClass();
        $releaseRejected = false;
        $runner = static function (callable $callback): void {
            if (method_exists(\Swoole\Coroutine::class, 'run')) {
                \Swoole\Coroutine::run($callback);
                return;
            }
            $scheduler = new \Swoole\Coroutine\Scheduler();
            $scheduler->add($callback);
            $scheduler->start();
        };

        $runner(static function () use ($pool, $resource, &$releaseRejected): void {
            \Swoole\Coroutine::create(static function () use ($pool, $resource, &$releaseRejected): void {
                try {
                    $pool->release($resource);
                } catch (LogicException) {
                    $releaseRejected = true;
                }
            });
            \Swoole\Coroutine::create(static function () use ($pool): void {
                \Swoole\Coroutine::sleep(0.005);
                $pool->clear();
            });
        });

        self::assertTrue($releaseRejected);
        self::assertSame(0, $pool->size());
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
