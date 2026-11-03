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

    public function testRejectsUnsafeIdentifiersAndOperators(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->database->table('users; DROP TABLE users')->count();
    }
}
