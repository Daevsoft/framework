<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Ds\Foundations\Commands\CronJob\Job;
use Throwable;

final class CronJob extends GeneratorCommand
{
    protected string $name = 'cronjob';
    protected string $description = 'Run one or more application jobs from the CLI';

    public function handle(): int
    {
        try {
            $names = $this->names();
            if ($names === []) {
                $directory = base_path('app/CronJobs');
                $files = glob($directory . DIRECTORY_SEPARATOR . '*.job.php') ?: [];
                $names = array_map(
                    static fn (string $file): string => basename($file, '.job.php'),
                    $files
                );
            }

            if ($names === []) {
                $this->info('No jobs found in app/cronjobs.');
                return 0;
            }

            foreach ($names as $name) {
                $segments = $this->segments($name);
                $class = $this->className($segments);
                $file = base_path('app/cronjobs/' . implode(DIRECTORY_SEPARATOR, $segments) . '.job.php');
                if (!is_file($file)) {
                    throw new \RuntimeException("Job file not found: {$file}");
                }

                require_once $file;
                $namespace = $this->namespaceFor('App\\CronJobs', $segments);
                $className = $namespace . '\\' . $class;
                if (!class_exists($className) || !is_subclass_of($className, Job::class)) {
                    throw new \RuntimeException("Job [{$className}] must extend " . Job::class . '.');
                }

                $job = new $className();
                $started = microtime(true);
                $job->run();
                $this->info(sprintf('%s completed in %.4fs', $name, microtime(true) - $started));
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
