<?php

declare(strict_types=1);

namespace Ds\Foundations\Common;

use Ds\View\ViewRenderer;

/** v3-compatible cache clearer for v4's content-addressed PieTemplate cache. */
class Cache
{
    public function __construct(private readonly ?string $cacheDirectory = null)
    {
    }

    public function run(): void
    {
        $this->clearAllPages();
    }

    public function clearAllPages(): int
    {
        $directory = $this->cacheDirectory ?? \base_path('app/storage/cache/views');
        if (!is_dir($directory)) {
            ViewRenderer::clearDefault();
            return 0;
        }

        $removed = 0;
        foreach (glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file) && (str_ends_with($file, '.php') || str_ends_with($file, '.tmp'))) {
                if (@unlink($file)) {
                    $removed++;
                }
            }
        }

        ViewRenderer::clearDefault();
        return $removed;
    }

    /** v4 cache files are content-addressed; there is no separate timestamp manifest. */
    public function resetCacheRecord(): void
    {
        ViewRenderer::clearDefault();
    }

    public function clearReferences(): void
    {
        // The v4 compiler has no mutable reference registry to clear.
    }
}
