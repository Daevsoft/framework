<?php

declare(strict_types=1);

namespace Tests\Database;

use Ds\Database\DatabaseManager;
use Ds\Database\Migrator;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private string $directory;
    private DatabaseManager $database;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ds-migrations-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0777, true);
        $this->database = new DatabaseManager(['driver' => 'sqlite', 'database' => ':memory:']);

        file_put_contents(
            $this->directory . '/2026_01_01_000001_create_notes.php',
            <<<'PHP'
<?php

use Ds\Database\Migration;
use PDO;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec('CREATE TABLE notes (id INTEGER PRIMARY KEY, body TEXT NOT NULL)');
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE notes');
    }
};
PHP
        );
    }

        file_put_contents(
            $this->directory . '/2026_01_02_000001_create_legacy_items.php',
            <<<'PHP'
<?php

use Ds\\Foundations\\Migrate\\Migrations;
use Ds\\Foundations\\Migrate\\Scheme;

return new class implements Migrations {
    public function up(Scheme $scheme)
    {
        $scheme->createTable('legacy_items', function ($table): void {
            $table->id();
            $table->string('label', 80)->notNull();
            $table->text('description')->nullable();
        });
    }

    public function down(Scheme $scheme)
    {
        $scheme->dropIfExist('legacy_items');
    }
};
PHP
        );

    protected function tearDown(): void
    {
        $this->database->shutdown();
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public function testRunsTracksAndRollsBackLegacySchemeMigrations(): void
    {
        $migrator = new Migrator($this->database);
        self::assertSame([
            '2026_01_01_000001_create_notes',
            '2026_01_02_000001_create_legacy_items',
        ], $migrator->migrate($this->directory));

        self::assertTrue($this->database->schema()->hasTable('legacy_items'));
        self::assertSame('2026_01_02_000001_create_legacy_items', $migrator->rollback($this->directory));
        self::assertFalse($this->database->schema()->hasTable('legacy_items'));
        self::assertSame('2026_01_01_000001_create_notes', $migrator->rollback($this->directory));
    }

    public function testRunsTracksAndRollsBackMigrations(): void
    {
        $migrator = new Migrator($this->database);
        $ran = $migrator->migrate($this->directory);

        self::assertSame(['2026_01_01_000001_create_notes'], $ran);
        self::assertSame(1, $this->database->select("SELECT COUNT(*) AS aggregate FROM sqlite_master WHERE type = 'table' AND name = 'notes'")[0]['aggregate']);
        self::assertTrue($migrator->status($this->directory)[0]['applied']);

        self::assertSame('2026_01_01_000001_create_notes', $migrator->rollback($this->directory));
        self::assertFalse($migrator->status($this->directory)[0]['applied']);
    }
}
