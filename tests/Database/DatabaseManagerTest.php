<?php

declare(strict_types=1);

namespace Tests\Database;

use Ds\Database\DatabaseManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseManagerTest extends TestCase
{
    private DatabaseManager $database;

    protected function setUp(): void
    {
        $this->database = new DatabaseManager([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->database->execute(
            'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, active INTEGER NOT NULL)'
        );
    }

    protected function tearDown(): void
    {
        $this->database->shutdown();
    }

    public function testParameterizedCrudQueries(): void
    {
        self::assertSame(1, $this->database->table('users')->insert([
            'name' => 'Deva',
            'active' => 1,
        ]));

        $user = $this->database->table('users')
            ->select(['id', 'name'])
            ->where('active', 1)
            ->first();

        self::assertSame('Deva', $user['name']);

        $updated = $this->database->table('users')
            ->where('id', $user['id'])
            ->update(['name' => 'Developer']);

        self::assertSame(1, $updated);
        self::assertSame(1, $this->database->table('users')->whereIn('name', ['Developer'])->count());

        self::assertSame(1, $this->database->table('users')->where('id', $user['id'])->delete());
        self::assertSame(0, $this->database->table('users')->count());
    }

    public function testAdvancedFiltersGroupingAndHaving(): void
    {
        $this->database->table('users')->insert(['name' => 'A', 'active' => 1]);
        $this->database->table('users')->insert(['name' => 'B', 'active' => 1]);
        $this->database->table('users')->insert(['name' => 'C', 'active' => 0]);

        self::assertSame(2, $this->database->table('users')->where('active', 1)->whereNotNull('name')->count());
        self::assertSame(2, count($this->database->table('users')->whereNotIn('name', ['C'])->get()));
        self::assertSame(2, count($this->database->table('users')->distinct()->select('active')->get()));
        self::assertCount(1, $this->database->table('users')->select('active')->groupBy('active')->having('active', '=', 1)->get());
        self::assertSame(3, count($this->database->table('users')->where('active', 1)->orWhere('name', 'C')->get()));
    }

    public function testInnerLeftAndRightJoinsUseValidatedIdentifiers(): void
    {
        $this->database->execute('CREATE TABLE profiles (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, city TEXT NOT NULL)');
        $this->database->table('users')->insert(['name' => 'Ada', 'active' => 1]);
        $this->database->table('users')->insert(['name' => 'Lin', 'active' => 0]);
        $this->database->table('profiles')->insert(['id' => 1, 'user_id' => 1, 'city' => 'London']);

        $inner = $this->database->table('users')
            ->select(['users.id', 'users.name', 'profiles.city'])
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->get();
        self::assertCount(1, $inner);
        self::assertSame('London', $inner[0]['city']);

        self::assertCount(2, $this->database->table('users')
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->get());

        self::assertCount(1, $this->database->table('users')
            ->rightJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->get());

        self::assertSame(1, $this->database->table('users')
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->where('profiles.city', 'London')
            ->count());
    }

    public function testGroupedWherePredicatesPreserveBooleanPrecedenceAndBindings(): void
    {
        $this->database->table('users')->insert(['name' => 'Ada', 'active' => 1]);
        $this->database->table('users')->insert(['name' => 'Lin', 'active' => 0]);
        $this->database->table('users')->insert(['name' => 'Mia', 'active' => 1]);

        $rows = $this->database->table('users')
            ->where('active', 1)
            ->whereGroup(static function ($query): void {
                $query->where('name', 'Ada')->orWhere('name', 'Mia');
            })
            ->get();

        self::assertCount(2, $rows);
        self::assertSame(['Ada', 'Mia'], array_column($rows, 'name'));

        $nested = $this->database->table('users')
            ->where('active', 0)
            ->orWhereGroup(static function ($query): void {
                $query->whereGroup(static function ($inner): void {
                    $inner->where('name', 'Ada')->orWhere('name', 'Mia');
                });
            })
            ->get();

        self::assertCount(3, $nested);

        self::assertCount(1, $this->database->table('users')
            ->orWhereGroup(static function ($query): void {
                $query->where('name', 'Ada');
            })
            ->get());

        self::assertCount(3, $this->database->table('users')
            ->whereGroup(static function ($query): void {
            })
            ->get());

        self::assertSame(1, $this->database->table('users')
            ->where('active', 1)
            ->whereGroup(static function ($query): void {
                $query->where('name', 'Mia');
            })
            ->update(['active' => 0]));

        self::assertSame(1, $this->database->table('users')
            ->where('active', 0)
            ->whereGroup(static function ($query): void {
                $query->where('name', 'Lin');
            })
            ->delete());
    }

    public function testJoinAliasesAndParameterizedSubqueries(): void
    {
        $this->database->execute('CREATE TABLE profiles (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, city TEXT NOT NULL)');
        $this->database->table('users')->insert(['name' => 'Ada', 'active' => 1]);
        $this->database->table('users')->insert(['name' => 'Lin', 'active' => 0]);
        $this->database->table('profiles')->insert(['id' => 1, 'user_id' => 1, 'city' => 'London']);

        $joined = $this->database->table('users')
            ->select(['users.name', 'p.city'])
            ->leftJoin('profiles', 'users.id', '=', 'p.user_id', 'p')
            ->where('p.city', 'London')
            ->get();

        self::assertCount(1, $joined);
        self::assertSame('London', $joined[0]['city']);

        $profileUsers = $this->database->table('profiles')
            ->select('user_id')
            ->where('city', 'London');

        $matched = $this->database->table('users')
            ->where('active', 1)
            ->whereInSubquery('id', $profileUsers)
            ->get();

        self::assertCount(1, $matched);
        self::assertSame('Ada', $matched[0]['name']);

        $notMatched = $this->database->table('users')
            ->whereNotInSubquery('id', $profileUsers)
            ->get();

        self::assertCount(1, $notMatched);
        self::assertSame('Lin', $notMatched[0]['name']);
    }

    public function testGroupedCountCountsResultGroups(): void
    {
        $this->database->table('users')->insert(['name' => 'Ada', 'active' => 1]);
        $this->database->table('users')->insert(['name' => 'Lin', 'active' => 0]);
        $this->database->table('users')->insert(['name' => 'Mia', 'active' => 1]);

        self::assertSame(
            2,
            $this->database->table('users')->select('active')->groupBy('active')->count()
        );

        self::assertSame(
            1,
            $this->database->table('users')
                ->select('active')
                ->groupBy('active')
                ->having('active', '=', 0)
                ->count()
        );
    }

    public function testJoinAliasRejectsUnsafeIdentifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->database->table('users')->leftJoin(
            'profiles',
            'users.id',
            '=',
            'p.user_id',
            'p; DROP TABLE users'
        );
    }

    public function testSubqueryMustShareDatabaseManager(): void
    {
        $otherDatabase = new DatabaseManager([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        try {
            $subquery = $otherDatabase->table('profiles')->select('user_id');
            $this->expectException(InvalidArgumentException::class);
            $this->database->table('users')->whereInSubquery('id', $subquery);
        } finally {
            $otherDatabase->shutdown();
        }
    }

    public function testSubqueryRequiresExactlyOneSelectedColumn(): void
    {
        $subquery = $this->database->table('users')->select(['id', 'name']);

        $this->expectException(InvalidArgumentException::class);
        $this->database->table('users')->whereInSubquery('id', $subquery)->get();
    }

    public function testTransactionRollsBackOnFailure(): void
    {
        try {
            $this->database->transaction(function ($pdo): void {
                $pdo->exec("INSERT INTO users (name, active) VALUES ('temporary', 1)");
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        self::assertSame(0, $this->database->table('users')->count());
    }

    public function testEmptyInAndNotInFiltersHaveExplicitSemantics(): void
    {
        $this->database->table('users')->insert(['name' => 'Ada', 'active' => 1]);

        self::assertCount(0, $this->database->table('users')->whereIn('id', [])->get());
        self::assertCount(1, $this->database->table('users')->whereNotIn('id', [])->get());

        self::assertCount(0, $this->database->table('users')
            ->where('active', 0)
            ->orWhereIn('id', [])
            ->get());
    }

    public function testShutdownIsIdempotentAndPreventsConnectionReuse(): void
    {
        $this->database->shutdown();
        $this->database->shutdown();

        $this->expectException(RuntimeException::class);
        $this->database->table('users')->count();
    }

    public function testShuttingDownOneManagerDoesNotInvalidateAnother(): void
    {
        $other = new DatabaseManager([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        try {
            $other->execute(
                'CREATE TABLE isolated (id INTEGER PRIMARY KEY, value TEXT NOT NULL)'
            );
            $this->database->shutdown();

            self::assertSame(1, $other->table('isolated')->insert(['value' => 'still-open']));
            self::assertSame(1, $other->table('isolated')->count());
        } finally {
            $other->shutdown();
        }
    }



    public function testShutdownDuringActiveCoroutineOperationDefersPoolClosure(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !class_exists(\Swoole\Coroutine\Channel::class)) {
            self::markTestSkipped('Swoole coroutine runtime is unavailable.');
        }

        $manager = new DatabaseManager([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'pool' => ['max' => 1, 'timeout' => 1],
        ]);
        $keyMethod = new \ReflectionMethod(DatabaseManager::class, 'poolKey');
        $key = $keyMethod->invoke($manager);
        $channels = new \ReflectionProperty(DatabaseManager::class, 'channels');
        $duringCallback = false;
        $afterCallback = true;

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
            $runner(static function () use ($manager, $channels, $key, &$duringCallback, &$afterCallback): void {
                $manager->execute('CREATE TABLE lifecycle_probe (id INTEGER PRIMARY KEY)');
                $manager->transaction(function (\PDO $pdo) use ($manager, $channels, $key, &$duringCallback): void {
                    $manager->shutdown();
                    $current = $channels->getValue();
                    $duringCallback = isset($current[$key]);
                });

                $current = $channels->getValue();
                $afterCallback = isset($current[$key]);
            });
        } finally {
            $manager->shutdown();
        }

        self::assertTrue($duringCallback, 'Pool channel must remain alive during an active callback.');
        self::assertFalse($afterCallback, 'Pool channel should close after the final lease is returned.');
    }


    public function testShutdownWaitsForBlockedCoroutinePoolWaiter(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !class_exists(\Swoole\Coroutine\Channel::class)) {
            self::markTestSkipped('Swoole coroutine runtime is unavailable.');
        }

        $manager = new DatabaseManager([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'pool' => ['max' => 1, 'timeout' => 2],
        ]);
        $key = (new \ReflectionMethod(DatabaseManager::class, 'poolKey'))->invoke($manager);
        $channels = new \ReflectionProperty(DatabaseManager::class, 'channels');
        $results = [];

        try {
            \Swoole\Coroutine::run(static function () use ($manager, &$results): void {
                \Swoole\Coroutine::create(static function () use ($manager, &$results): void {
                    $manager->transaction(function (\PDO $pdo) use ($manager, &$results): void {
                        $results['holder_entered'] = true;
                        \Swoole\Coroutine::sleep(0.08);
                        $manager->shutdown();
                    });
                    $results['holder_finished'] = true;
                });

                \Swoole\Coroutine::create(static function () use ($manager, &$results): void {
                    while (!($results['holder_entered'] ?? false)) {
                        \Swoole\Coroutine::sleep(0.001);
                    }

                    // Give the holder time to lease the only connection so this
                    // operation is blocked in Channel::pop when shutdown occurs.
                    \Swoole\Coroutine::sleep(0.01);
                    $results['waiter_result'] = $manager->select('SELECT 42 AS answer');
                });
            });
        } finally {
            $manager->shutdown();
        }

        self::assertTrue($results['holder_finished'] ?? false);
        self::assertSame(42, $results['waiter_result'][0]['answer'] ?? null);
        self::assertArrayNotHasKey($key, $channels->getValue());
    }

    public function testPoolShutdownWaitsUntilActiveOperationsAreReleased(): void
    {
        $channel = new class {
            public bool $closed = false;

            public function close(): void
            {
                $this->closed = true;
            }
        };

        $channels = new \ReflectionProperty(DatabaseManager::class, 'channels');
        $created = new \ReflectionProperty(DatabaseManager::class, 'created');
        $users = new \ReflectionProperty(DatabaseManager::class, 'poolUsers');
        $leases = new \ReflectionProperty(DatabaseManager::class, 'activeOperations');
        $channels->setValue(null, ['lease-test' => $channel]);
        $created->setValue(null, ['lease-test' => 1]);
        $users->setValue(null, []);
        $leases->setValue(null, ['lease-test' => 1]);

        $closePool = new \ReflectionMethod(DatabaseManager::class, 'closePoolIfUnused');
        $closePool->invoke($this->database, 'lease-test');
        self::assertFalse($channel->closed);

        $leases->setValue(null, []);
        $closePool->invoke($this->database, 'lease-test');

        self::assertTrue($channel->closed);
        self::assertSame([], $channels->getValue());
    }

    public function testRejectsUnsafeIdentifiersAndOperators(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->database->table('users; DROP TABLE users')->count();
    }
}
