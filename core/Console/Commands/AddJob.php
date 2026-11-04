<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Throwable;

final class AddJob extends GeneratorCommand
{
    protected string $name = 'add:job';
    protected string $description = 'Generate a v3-compatible job that can run in task workers';

    public function handle(): int
    {
        try {
            $names = $this->names();
            if ($names === []) {
                $this->error('Usage: php ds add:job Maintenance/PruneLogs');
                return 1;
            }

            foreach ($names as $name) {
                $segments = $this->segments($name);
                $class = $this->className($segments);
                $namespace = $this->namespaceFor('App\\CronJobs', $segments);
                $relative = 'app/CronJobs/' . implode('/', $segments) . '.job.php';
                $content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\n"
                    . "use Ds\\Foundations\\Commands\\CronJob\\Job;\n\n"
                    . "final class {$class} extends Job\n{\n"
                    . "    protected function start(): void\n    {\n"
                    . "        // Implement the job. It can be run with php ds cronjob {$name}.\n"
                    . "    }\n}\n";
                $this->write($relative, $content);
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
