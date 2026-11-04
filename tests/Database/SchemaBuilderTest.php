<?php

declare(strict_types=1);

namespace Tests\Database;

use Ds\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;

final class SchemaBuilderTest extends TestCase
{
    private DatabaseManager $database;

    protected function setUp(): void
    {
        $this->database = new DatabaseManager(['driver' => 'sqlite', 'database' => ':memory:']);
    }

    protected function tearDown(): void
    {
        $this->database->shutdown();
    }

    public function testCreatesAndDropsTableWithColumnDefinitions(): void
    {
        $this->database->schema()->create('schema_notes', static function ($table): void {
            $table->id();
            $table->string('title', 120);
            $table->text('body')->nullable();
            $table->boolean('published')->default(false);
        });

        self::assertTrue($this->database->schema()->hasTable('schema_notes'));
        $this->database->execute(
            'INSERT INTO schema_notes (title, published) VALUES (:title, :published)',
            ['title' => 'Hello', 'published' => 0]
        );
        self::assertSame('Hello', $this->database->select('SELECT title FROM schema_notes')[0]['title']);

        $this->database->schema()->dropIfExists('schema_notes');
        self::assertFalse($this->database->schema()->hasTable('schema_notes'));
    }

    public function testRejectsInvalidColumnNamesAndEmptyTables(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->database->schema()->create('invalid_table', static function ($table): void {
            $table->string('invalid-name');
        });
    }

    public function testAddsAndDropsColumnsWithValidatedDefinitions(): void
    {
        $schema = $this->database->schema();
        $schema->create('schema_alter_notes', static function ($table): void {
            $table->id();
            $table->string('title', 80);
        });

        $schema->addColumn('schema_alter_notes', static function ($table): void {
            $table->boolean('published')->default(false);
        });
        $columns = $this->database->select('PRAGMA table_info(schema_alter_notes)');
        self::assertContains('published', array_column($columns, 'name'));

        $schema->dropColumn('schema_alter_notes', 'published');
        $columns = $this->database->select('PRAGMA table_info(schema_alter_notes)');
        self::assertNotContains('published', array_column($columns, 'name'));
    }

    public function testAlterAddRequiresExactlyOneColumn(): void
    {
        $this->database->schema()->create('schema_alter_invalid', static function ($table): void {
            $table->id();
        });

        $this->expectException(\\LogicException::class);
        $this->database->schema()->addColumn('schema_alter_invalid', static function ($table): void {
            $table->string('first');
            $table->string('second');
        });
    }

    public function testDropIfExistsIsIdempotent(): void
    {
        $this->database->schema()->dropIfExists('missing_table');
        self::assertFalse($this->database->schema()->hasTable('missing_table'));
    }
}
