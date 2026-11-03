<?php

declare(strict_types=1);

namespace Ds\Foundations\View;

use Ds\View\ViewRenderer;

/**
 * Backward-compatible v3 view facade.
 *
 * Rendering is delegated to the v4 renderer so callers retain the familiar
 * static API without reviving the old provider/cache implementation.
 */
final class View
{
    public static function contents(string $viewName, ?array $data = null): string
    {
        return ViewRenderer::default()->render($viewName, $data ?? []);
    }

    public static function filename(string $viewName): string
    {
        return str_replace('.', DIRECTORY_SEPARATOR, $viewName);
    }
}
