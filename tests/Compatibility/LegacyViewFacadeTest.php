<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Foundations\View\View as LegacyView;
use Ds\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

final class LegacyViewFacadeTest extends TestCase
{
    public function testLegacyFilenameUsesPlatformDirectorySeparator(): void
    {
        self::assertSame(
            'pages' . DIRECTORY_SEPARATOR . 'welcome',
            LegacyView::filename('pages.welcome')
        );
    }

    public function testLegacyContentsDelegatesToPieTemplateRenderer(): void
    {
        $name = 'legacy-facade-' . bin2hex(random_bytes(6));
        $views = dirname(__DIR__, 2) . '/app/views';
        $filename = $views . '/' . $name . '.pie.php';

        file_put_contents($filename, '<h1>{{ $message }}</h1>');
        ViewRenderer::clearDefault();

        try {
            self::assertSame('<h1>Compatible</h1>', LegacyView::contents($name, [
                'message' => 'Compatible',
            ]));
        } finally {
            @unlink($filename);
            ViewRenderer::clearDefault();
        }
    }
}
