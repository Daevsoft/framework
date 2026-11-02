<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Throwable;

final class AddView extends GeneratorCommand
{
    protected string $name = 'add:view';
    protected string $description = 'Generate one or more PieTemplate views';

    public function handle(): int
    {
        try {
            $names = $this->names();
            if ($names === []) {
                $this->error('Usage: php ds add:view pages/home');
                return 1;
            }

            foreach ($names as $name) {
                $segments = $this->segments($name);
                $relative = 'app/views/' . implode('/', $segments) . '.pie.php';
                $this->write($relative, "<h1>{{ \$title ?? 'View' }}</h1>\n");
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
