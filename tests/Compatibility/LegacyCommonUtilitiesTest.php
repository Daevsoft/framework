<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Foundations\Common\Cache;
use Ds\Foundations\Common\File;
use Ds\Foundations\Common\Func;
use PHPUnit\Framework\TestCase;

final class LegacyCommonUtilitiesTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ds-legacy-common-' . bin2hex(random_bytes(5));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->directory);
    }

    public function testLegacyFileFacadeSupportsSafeChainedOperations(): void
    {
        $path = $this->directory . '/nested/example.txt';
        $file = (new File($path))->create('one')->append('two');

        self::assertSame('example.txt', $file->getFilename());
        self::assertSame('onetwo', $file->getContent());

        $file->rewrite('replaced')->close();
        self::assertSame('replaced', $file->getContent());
        self::assertTrue($file->delete());
        self::assertFalse(file_exists($path));
        @rmdir(dirname($path));
    }

    public function testLegacyCacheAdapterOnlyRemovesGeneratedCacheFiles(): void
    {
        file_put_contents($this->directory . '/compiled.php', '<?php return [];');
        file_put_contents($this->directory . '/partial.tmp', 'temp');
        file_put_contents($this->directory . '/keep.txt', 'keep');

        $cache = new Cache($this->directory);
        self::assertSame(2, $cache->clearAllPages());
        self::assertFileExists($this->directory . '/keep.txt');
        self::assertFileDoesNotExist($this->directory . '/compiled.php');
        self::assertFileDoesNotExist($this->directory . '/partial.tmp');
    }

    public function testLegacyPathAndDebugHelpersAreDeterministic(): void
    {
        self::assertSame(DIRECTORY_SEPARATOR === '/', Func::isSlash());
        self::assertSame(
            DIRECTORY_SEPARATOR === '/' ? 'a/b' : 'a\\b',
            Func::replaceSlash('a\\b')
        );

        ob_start();
        Func::check('<script>', true);
        $output = ob_get_clean();
        self::assertStringContainsString('&lt;script&gt;', $output);
    }
}
