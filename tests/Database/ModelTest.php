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

    public function testHydratedModelsAndRelationshipQueries(): void
    {
        $this->database->execute('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL)');
        $this->database->table('people')->insert(['name' => 'Deva', 'active' => 1]);
        $this->database->table('posts')->insert(['user_id' => 1, 'title' => 'First']);
        $this->database->table('posts')->insert(['user_id' => 1, 'title' => 'Second']);

        $person = TestPersonModel::findModel(1);
        self::assertSame('Deva', $person->name);
        self::assertSame(2, $person->hasMany(TestPostModel::class, 'user_id')->count());
        self::assertSame('First', $person->hasOne(TestPostModel::class, 'user_id')->first()['title']);

        $post = new TestPostModel(['user_id' => 1]);
        self::assertSame('Deva', $post->belongsTo(TestPersonModel::class, 'user_id')->first()['name']);
        self::assertSame('Deva', TestPersonModel::allModels()[0]->name);
    }

    public function testSaveWithNoFillableInsertFieldsIsANoOp(): void
    {
        self::assertSame(0, TestPersonModel::save(['is_admin' => true]));
        self::assertSame(0, TestPersonModel::count());
    }

    public function testSaveUpdatesByGuardedPrimaryKey(): void
    {
        self::assertSame(1, TestPersonModel::save(['name' => 'Ada', 'active' => 1]));

        self::assertSame(1, TestPersonModel::save([
            'id' => 1,
            'name' => 'Grace',
            'active' => 1,
            'is_admin' => true,
        ]));

        self::assertSame(1, TestPersonModel::count());
        self::assertSame('Grace', TestPersonModel::find(1)['name']);
        self::assertArrayNotHasKey('is_admin', TestPersonModel::find(1));
    }

    public function testUpdateByIdWithNoFillableChangesIsANoOp(): void
    {
        self::assertSame(1, TestPersonModel::save(['name' => 'Ada', 'active' => 1]));

        self::assertSame(0, TestPersonModel::updateById(1, [
            'id' => 1,
            'is_admin' => true,
        ]));
        self::assertSame('Ada', TestPersonModel::find(1)['name']);
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

final class TestPostModel extends Model
{
    protected $table = 'posts';
}

final class TestPersonModel extends Model
{
    protected $table = 'people';
    protected $fillable = ['name', 'active'];
}
