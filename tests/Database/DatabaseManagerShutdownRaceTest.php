<?php

declare(strict_types=1);

namespace Tests\Database;

use Ds\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;

final class DatabaseManagerShutdownRaceTest extends TestCase
{
    public function testSharedManagersCanShutdownDuringAnActiveCoroutineLease(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !class_exists(\Swoole\Coroutine\Channel::class)) {
            self::markTestSkipped('Swoole coroutine runtime is unavailable.');
        }

        $config = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'pool' => ['max' => 1, 'timeout' => 2],
        ];
        $first = new DatabaseManager($config);
        $second = new DatabaseManager($config);
        $key = (new \ReflectionMethod(DatabaseManager::class, 'poolKey'))->invoke($first);
        $channels = new \ReflectionProperty(DatabaseManager::class, 'channels');
        $active = new \ReflectionProperty(DatabaseManager::class, 'activeOperations');
        $results = [];

        $runner = static function (callable $callback): void {
            if (method_exists(\Swoole\Coroutine::class, 'run')) {
                \Swoole\Coroutine::run($callback);
                return;
            }

            if (!class_exists(\Swoole\Coroutine\Scheduler::class)) {
                self::markTestSkipped('No supported Swoole coroutine scheduler is available.');
            }

            $scheduler = new \Swoole\Coroutine\Scheduler();
            $scheduler->add($callback);
            $scheduler->start();
        };

        try {
            $runner(static function () use ($first, $second, $key, $channels, $active, &$results): void {
                \Swoole\Coroutine::create(static function () use ($first, &$results): void {
                    $results['result'] = $first->transaction(static function (\PDO $pdo): int {
                        \Swoole\Coroutine::sleep(0.05);
                        return (int) $pdo->query('SELECT 7')->fetchColumn();
                    });
                });

                \Swoole\Coroutine::create(static function () use ($first, $second, $key, $channels, $active, &$results): void {
                    for ($attempt = 0; $attempt < 1000; $attempt++) {
                        $counts = $active->getValue();
                        if (($counts[$key] ?? 0) > 0) {
                            break;
                        }
                        \Swoole\Coroutine::sleep(0.001);
                    }

                    $first->shutdown();
                    $second->shutdown();
                    $counts = $active->getValue();
                    $results['active_after_shutdown'] = $counts[$key] ?? 0;
                    $results['channel_open_after_shutdown'] = isset($channels->getValue()[$key]);
                });
            });
        } finally {
            $first->shutdown();
            $second->shutdown();
        }

        self::assertSame(7, $results['result'] ?? null);
        self::assertSame(1, $results['active_after_shutdown'] ?? null);
        self::assertTrue($results['channel_open_after_shutdown'] ?? false);
        self::assertArrayNotHasKey($key, $channels->getValue());
    }
}
