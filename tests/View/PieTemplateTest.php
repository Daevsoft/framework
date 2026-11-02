<?php

declare(strict_types=1);

namespace Tests\View;

use Ds\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

final class PieTemplateTest extends TestCase
{
    private string $root;
    private string $views;
    private string $cache;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/ds-pie-' . bin2hex(random_bytes(6));
        $this->views = $this->root . '/views';
        $this->cache = $this->root . '/cache';
        mkdir($this->views . '/layouts', 0777, true);
        mkdir($this->views . '/pages', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testRendersEscapedAndRawExpressions(): void
    {
        file_put_contents(
            $this->views . '/welcome.pie.php',
            '<h1>{{ $title }}</h1><p>{! $html !}</p>'
        );

        $html = (new ViewRenderer($this->views, $this->cache))->render('welcome', [
            'title' => 'Hello',
            'html' => '<strong>Safe text</strong>',
        ]);

        self::assertSame(
            '<h1>Hello</h1><p>&lt;strong&gt;Safe text&lt;/strong&gt;</p>',
            $html
        );
    }

    public function testRendersLayoutAndNamedPartSlots(): void
    {
        file_put_contents(
            $this->views . '/layouts/app.pie.php',
            '<html><title>@slot("title")</title><body>@slot("content")</body></html>'
        );
        file_put_contents(
            $this->views . '/pages/home.pie.php',
            "@use('layouts.app')\n@part('title'){{ \$title }}@endpart\n@part('content')<h1>{{ \$heading }}</h1>@endpart"
        );

        $html = (new ViewRenderer($this->views, $this->cache))->render('pages.home', [
            'title' => 'Home',
            'heading' => 'Welcome',
        ]);

        self::assertSame('<html><title>Home</title><body><h1>Welcome</h1></body></html>', $html);
    }

    public function testCompilesConditionsAndLoops(): void
    {
        file_put_contents(
            $this->views . '/list.pie.php',
            '@if($enabled):<ul>@foreach($items as $item):<li>{{ $item }}</li>@endforeach</ul>@else:<p>Disabled</p>@endif'
        );

        $renderer = new ViewRenderer($this->views, $this->cache);
        self::assertSame(
            '<ul><li>one</li><li>two</li></ul>',
            $renderer->render('list', ['enabled' => true, 'items' => ['one', 'two']])
        );
        self::assertSame(
            '<p>Disabled</p>',
            $renderer->render('list', ['enabled' => false, 'items' => []])
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
