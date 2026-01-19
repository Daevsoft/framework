<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Ds\Console\Command;

class MakeDomain extends Command
{
    /**
     * Command name and description
     */
    protected string $name = 'make:domain';
    protected string $description = 'Create a new domain structure';

    /**
     * Execute the command
     */
    public function handle(): int
    {
        $name = $this->ask('Domain name:');

        if (empty($name)) {
            $this->error('Domain name cannot be empty');
            return 1;
        }

        $domainPath = app_path("domains/{$name}");

        if (is_dir($domainPath)) {
            $this->error("Domain {$name} already exists");
            return 1;
        }

        // Create directory structure
        $dirs = [
            'Http/Controllers',
            'Http/Requests',
            'Services',
            'Models',
            'Repositories',
            'Policies',
            'Events',
            'Listeners',
            'Broadcast/Events',
            'Broadcast/Channels',
        ];

        foreach ($dirs as $dir) {
            @mkdir("{$domainPath}/{$dir}", 0755, true);
        }

        // Create basic files
        file_put_contents("{$domainPath}/README.md", "# {$name} Domain\n\nThis is the {$name} domain module.\n");
        file_put_contents("{$domainPath}/Http/Routes.php", "<?php\n\ndeclare(strict_types=1);\n\n// Routes for {$name} domain\n");
        file_put_contents("{$domainPath}/Http/Middleware.php", "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Domains\\{$name}\\Http;\n\n// Domain middleware\n");

        $this->info("Domain {$name} created successfully!");
        return 0;
    }
}


