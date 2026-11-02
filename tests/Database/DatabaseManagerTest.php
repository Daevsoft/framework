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
