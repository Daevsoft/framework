<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Ds\Console\Command;
use Ds\Database\Migrator;
use Throwable;

final class Migrate extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run or roll back database migrations';

    public function handle(): int
    {
        try {
            $migrator = new Migrator(\db());
            if (in_array('--rollback', $this->parameters, true)) {
                $rolledBack = $migrator->rollback();
                $this->info($rolledBack === null ? 'No migration to roll back.' : "Rolled back: {$rolledBack}");
                return 0;
            }

            if (in_array('--status', $this->parameters, true)) {
                foreach ($migrator->status(\base_path('database/migrations')) as $migration) {
                    $state = $migration['applied'] ? 'Ran' : 'Pending';
                    $this->info($state . ': ' . $migration['name']);
                }
                return 0;
            }

            $ran = $migrator->migrate(\base_path('database/migrations'));
            foreach ($ran as $name) {
                $this->info("Migrated: {$name}");
            }
            if ($ran === []) {
                $this->info('No pending migrations.');
            }
            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
