<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Throwable;

final class AddController extends GeneratorCommand
{
    protected string $name = 'add:controller';
    protected string $description = 'Generate one or more HTTP controllers';

    public function handle(): int
    {
        try {
            $names = $this->names();
            if ($names === []) {
                $this->error('Usage: php ds add:controller UserController [Admin/PostController]');
                return 1;
            }

            foreach ($names as $name) {
                $segments = $this->segments($name);
                $class = $this->className($segments, 'Controller');
                $segments[array_key_last($segments)] = $class;
                $namespace = $this->namespaceFor('App\\Http\\Controllers', $segments);
                $relative = 'app/http/Controllers/' . implode('/', $segments) . '.php';
                $content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse Ds\\Http\\Request;\nuse Ds\\Http\\Response;\n\nfinal class {$class}\n{\n    public function index(Request \$request): Response\n    {\n        return Response::text('{$class}');\n    }\n}\n";
                $this->write($relative, $content);
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
