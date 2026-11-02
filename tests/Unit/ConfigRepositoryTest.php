<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Config\ConfigRepository;
use PHPUnit\Framework\TestCase;

final class ConfigRepositoryTest extends TestCase
{
    private string $directory;
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ds-config-' . bin2hex(random_bytes(6));
        $this->cacheFile = $this->directory . '/cache/config.php';
        mkdir($this->directory . '/source', 0777, true);
        file_put_contents(
            $this->directory . '/source/app.php',
            "<?php return ['name' => 'first', 'debug' => false];"
        );
        ConfigRepository::reset();
    }

    protected function tearDown(): void
    {
        ConfigRepository::reset();
        if (is_file($this->cacheFile)) {
            unlink($this->cacheFile);
        }
        if (is_dir(dirname($this->cacheFile))) {
            rmdir(dirname($this->cacheFile));
        }
        if (is_file($this->directory . '/source/app.php')) {
            unlink($this->directory . '/source/app.php');
        }
        rmdir($this->directory . '/source');
        rmdir($this->directory);
    }

    public function testBuildsAndReadsCompiledConfigurationCache(): void
    {
        ConfigRepository::cache($this->directory . '/source', $this->cacheFile);
        self::assertSame('first', ConfigRepository::get(
            'app.name',
            null,
            $this->directory . '/source',
            $this->cacheFile
        ));

        file_put_contents(
            $this->directory . '/source/app.php',
            "<?php return ['name' => 'second'];"
        );
        ConfigRepository::reset();

        self::assertSame('first', ConfigRepository::get(
            'app.name',
            null,
            $this->directory . '/source',
            $this->cacheFile
        ));

        ConfigRepository::clear($this->cacheFile);
        self::assertSame('second', ConfigRepository::get(
            'app.name',
            null,
            $this->directory . '/source',
            $this->cacheFile
        ));
    }

    public function testReturnsDefaultsForMissingKeys(): void
    {
        self::assertSame('fallback', ConfigRepository::get(
            'app.missing',
            'fallback',
            $this->directory . '/source',
            $this->cacheFile
        ));
    }
}
