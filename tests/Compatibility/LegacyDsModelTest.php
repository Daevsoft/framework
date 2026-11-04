<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Database\DatabaseManager;
use Ds\Foundations\Connection\Models\DsModel;
use PHPUnit\Framework\TestCase;

final class LegacyDsModelTest extends TestCase
{
    private DatabaseManager $database;

    protected function setUp(): void
    {
        $this->database = new DatabaseManager(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->database->execute(
            'CREATE TABLE legacy_people (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, active INTEGER NOT NULL, admin INTEGER)'
        );
        DatabaseManager::setDefault($this->database);
    }

    protected function tearDown(): void
    {
        DatabaseManager::setDefault(null);
        $this->database->shutdown();
    }

    public function testLegacyCrudAndFillableFiltering(): void
    {
        $id = LegacyPersonModel::save(['name' => 'Ada', 'active' => 1, 'admin' => 1]);
        self::assertSame(1, $id);
        self::assertSame('Ada', LegacyPersonModel::find($id)['name']);
        self::assertNull(LegacyPersonModel::find($id)['admin']);
        self::assertSame(1, LegacyPersonModel::count());

        LegacyPersonModel::save(['id' => $id, 'name' => 'Grace', 'admin' => 1]);
        self::assertSame('Grace', LegacyPersonModel::findBy('active', 1)['name']);
        self::assertSame([['name' => 'Grace']], LegacyPersonModel::findsBy('active', 1, ['name']));
        self::assertSame(1, LegacyPersonModel::remove($id));
        self::assertSame([], LegacyPersonModel::all());
    }

    public function testLegacyBulkSaveAndConditionLookup(): void
    {
        self::assertSame(2, LegacyPersonModel::save([
            ['name' => 'Ada', 'active' => 1],
            ['name' => 'Lin', 'active' => 0],
        ]));
        self::assertSame('Ada', LegacyPersonModel::findsWhere(['active' => 1])[0]['name']);
        self::assertNotNull(LegacyPersonModel::findIsNull('admin'));
        self::assertNull(LegacyPersonModel::findIsNull('admin')['admin']);
    }

    public function testLegacyNullLookupsAndSafeQueryHelpers(): void
    {
        LegacyPersonModel::save([
            ['name' => 'Ada', 'active' => 1],
            ['name' => 'Grace', 'active' => 1],
        ]);
        $this->database->execute('UPDATE legacy_people SET admin = 1 WHERE name = :name', ['name' => 'Grace']);

        self::assertSame('Grace', LegacyPersonModel::findIsNotNull('admin')['name']);
        self::assertCount(1, LegacyPersonModel::findsIsNull('admin'));
        self::assertCount(1, LegacyPersonModel::findsIsNotNull('admin'));
        self::assertCount(2, (new LegacyPersonModel())->whereIn('active', [1])->get());
        self::assertCount(1, (new LegacyPersonModel())->whereNotIn('name', ['Ada'])->get());
        self::assertCount(1, (new LegacyPersonModel())->like('name', 'Gr%')->get());
        self::assertCount(2, (new LegacyPersonModel())->orWhereIn('name', ['Ada'])->orWhereIn('name', ['Grace'])->get());
        self::assertCount(1, (new LegacyPersonModel())->not_equal('name', 'Ada')->get());
    }

    public function testLegacySelectChainAndComparisonOperators(): void
    {
        LegacyPersonModel::save([
            ['name' => 'Ada', 'active' => 1],
            ['name' => 'Grace', 'active' => 1],
            ['name' => 'Lin', 'active' => 0],
        ]);

        $selected = (new LegacyPersonModel())
            ->select(['name'])
            ->where('active', '=', 1)
            ->orderBy('name')
            ->get_object();
        self::assertSame([['name' => 'Ada'], ['name' => 'Grace']], $selected);

        self::assertSame('Grace', LegacyPersonModel::where('name', 'LIKE', 'Gr%')->first()['name']);
        self::assertSame('Ada', LegacyPersonModel::orWhere('name', 'Ada')->first()['name']);
        self::assertTrue((new LegacyPersonModel())->whereIn('active', [1])->get_exist());
        self::assertSame('Ada', (new LegacyPersonModel())->select()->where('name', 'Ada')->row()['name']);
    }

    public function testFillableHelperAndExplicitTableAreAvailable(): void
    {
        $model = new LegacyPersonModel();
        self::assertSame('legacy_people', $model->table);
        self::assertSame(['name' => 'Ada'], $model->filledFields([
            'name' => 'Ada',
            'admin' => 1,
        ]));
    }
}

final class LegacyPersonModel extends DsModel
{
    public $table = 'legacy_people';
    protected $fillable = ['name', 'active'];
}
