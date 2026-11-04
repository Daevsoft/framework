<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Throwable;

final class AddHelper extends GeneratorCommand
{
    protected string $name = 'add:helper';
    protected string $description = 'Generate one or more application helper classes';

    public function handle(): int
    {
        try {
            $names = $this->names();
            if ($names === []) {
                $this->error('Usage: php ds add:helper Formatting/Money');
                return 1;
            }

            foreach ($names as $name) {
                $segments = $this->segments($name);
                $class = $this->className($segments);
                $namespace = $this->namespaceFor('App\\Helpers', $segments);
                $relative = 'app/helpers/' . implode('/', $segments) . '.php';
                $content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\n"
                    . "final class {$class}\n{\n}\n";
                $this->write($relative, $content);
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
