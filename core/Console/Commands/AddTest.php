<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Throwable;

final class AddTest extends GeneratorCommand
{
    protected string $name = 'add:test';
    protected string $description = 'Generate PHPUnit test classes';

    public function handle(): int
    {
        try {
            $unit = in_array('--unit', $this->parameters, true);
            $names = $this->names();
            if ($names === []) {
                $this->error('Usage: php ds add:test UserTest [OtherTest] [--unit]');
                return 1;
            }

            foreach ($names as $name) {
                $segments = $this->segments($name);
                $class = $this->className($segments, 'Test');
                $relative = 'tests/' . ($unit ? 'Unit/' : 'Feature/') . implode('/', array_slice($segments, 0, -1)) . ($segments === [] ? '' : '/') . $class . '.php';
                $content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Tests\\" . ($unit ? 'Unit' : 'Feature') . ";\n\nuse PHPUnit\\Framework\\TestCase;\n\nfinal class {$class} extends TestCase\n{\n    public function testExample(): void\n    {\n        self::assertTrue(true);\n    }\n}\n";
                $this->write($relative, $content);
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
