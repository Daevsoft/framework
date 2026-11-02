<?php

declare(strict_types=1);

namespace Tests\Database;

use Ds\Database\DatabaseManager;
use Ds\Database\Model;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    private DatabaseManager $database;

    protected function setUp(): void
    {
        $this->database = new DatabaseManager(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->database->execute(
            'CREATE TABLE people (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, active INTEGER NOT NULL)'
        );
        DatabaseManager::setDefault($this->database);
    }

    protected function tearDown(): void
    {
        DatabaseManager::setDefault(null);
        $this->database->shutdown();
    }

    public function testV3CompatibleModelCrudAndFillableWhitelist(): void
    {
        $inserted = TestPersonModel::save([
            'name' => 'Deva',
            'active' => 1,
            'is_admin' => true,
        ]);

        self::assertSame(1, $inserted);
        self::assertSame(1, TestPersonModel::count());
        self::assertSame('Deva', TestPersonModel::find(1)['name']);
        self::assertArrayNotHasKey('is_admin', TestPersonModel::find(1));

        TestPersonModel::updateById(1, ['name' => 'Developer', 'is_admin' => true]);
        self::assertSame('Developer', TestPersonModel::where('active', 1)->first()['name']);

        self::assertSame(1, TestPersonModel::deleteById(1));
        self::assertSame([], TestPersonModel::all());
    }
}

final class TestPersonModel extends Model
{
    protected $table = 'people';
    protected $fillable = ['name', 'active'];
}
