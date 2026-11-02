<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Throwable;

final class AddModel extends GeneratorCommand
{
    protected string $name = 'add:model';
    protected string $description = 'Generate one or more database models';

    public function handle(): int
    {
        try {
            $names = $this->names();
            if ($names === []) {
                $this->error('Usage: php ds add:model People');
                return 1;
            }

            foreach ($names as $name) {
                $segments = $this->segments($name);
                $class = $this->className($segments);
                $segments[array_key_last($segments)] = $class;
                $namespace = $this->namespaceFor('App\\Models', $segments);
                $table = $this->snake($class);
                $relative = 'app/models/' . implode('/', $segments) . '.php';
                $content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse Ds\\Foundations\\Connection\\Models\\DsModel;\n\nfinal class {$class} extends DsModel\n{\n    public \$table = '{$table}';\n    protected \$fillable = [];\n}\n";
                $this->write($relative, $content);
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
