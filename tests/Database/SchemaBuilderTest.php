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

    public function testDropIfExistsIsIdempotent(): void
    {
        $this->database->schema()->dropIfExists('missing_table');
        self::assertFalse($this->database->schema()->hasTable('missing_table'));
    }
}
