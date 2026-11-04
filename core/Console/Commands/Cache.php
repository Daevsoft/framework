<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Ds\Foundations\Common\Cache as ViewCache;
use Throwable;

final class Cache extends GeneratorCommand
{
    protected string $name = 'cache';
    protected string $description = 'Clear compiled PieTemplate view cache';

    public function handle(): int
    {
        try {
            $removed = (new ViewCache())->clearAllPages();
            $this->info("Removed {$removed} compiled view cache file(s).");
            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
