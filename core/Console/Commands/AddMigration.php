<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Throwable;

final class AddMigration extends GeneratorCommand
{
    protected string $name = 'add:migration';
    protected string $description = 'Generate a database migration file';

    public function handle(): int
    {
        try {
            $names = $this->names();
            if ($names === []) {
                $this->error('Usage: php ds add:migration create_users_table');
                return 1;
            }

            foreach ($names as $name) {
                $segments = $this->segments($name);
                $migration = $this->snake(implode('_', $segments));
                $filename = date('Y_m_d_His') . '_' . $migration . '.php';
                $content = "<?php\n\ndeclare(strict_types=1);\n\nuse Ds\\Database\\Migration;\nuse PDO;\n\nreturn new class extends Migration {\n    public function up(PDO \$connection): void\n    {\n        // Add the forward SQL statement here.\n    }\n\n    public function down(PDO \$connection): void\n    {\n        // Add the rollback SQL statement here.\n    }\n};\n";
                $this->write('database/migrations/' . $filename, $content);
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
