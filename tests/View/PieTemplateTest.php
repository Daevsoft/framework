<?php

declare(strict_types=1);

namespace Tests\View;

use Ds\View\PieCompiler;
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
            "@use('layouts.app')@part('title'){{ \$title }}@endpart@part('content')<h1>{{ \$heading }}</h1>@endpart"
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

    public function testCompilesNestedExpressionsAndElseIf(): void
    {
        file_put_contents(
            $this->views . '/nested.pie.php',
            '@if(is_array($value) && count($value) > 0):' .
            '{{ $value[0] }}@elseif(($fallback ?? null) !== null):{{ $fallback }}@else:empty@endif'
        );

        $renderer = new ViewRenderer($this->views, $this->cache);
        self::assertSame('first', $renderer->render('nested', [
            'value' => ['first'],
            'fallback' => 'backup',
        ]));
        self::assertSame('backup', $renderer->render('nested', [
            'value' => [],
            'fallback' => 'backup',
        ]));
        self::assertSame('empty', $renderer->render('nested', [
            'value' => [],
            'fallback' => null,
        ]));
    }

    public function testCompilesSwitchCaseAndDefaultDirectives(): void
    {
        file_put_contents(
            $this->views . '/switch.pie.php',
            "@switch(\$status)@case('ready')Ready@break@case('pending')Pending@break@default:Unknown@endswitch"
        );

        $renderer = new ViewRenderer($this->views, $this->cache);
        self::assertSame('Ready', $renderer->render('switch', ['status' => 'ready']));
        self::assertSame('Pending', $renderer->render('switch', ['status' => 'pending']));
        self::assertSame('Unknown', $renderer->render('switch', ['status' => 'other']));
    }

    public function testRendersImportedPieComponentMacros(): void
    {
        mkdir($this->views . '/components', 0777, true);
        file_put_contents(
            $this->views . '/components/button.pie.php',
            "@pie('primary')<button>{{ \$label }}</button>@endpie"
        );
        file_put_contents(
            $this->views . '/component-page.pie.php',
            "@import('components.button', 'ui')@ui('primary')"
        );

        $html = (new ViewRenderer($this->views, $this->cache))->render('component-page', [
            'label' => 'Save',
        ]);

        self::assertSame('<button>Save</button>', $html);
    }

    public function testCompilesLegacyAuthFlashCsrfAndAssetDirectives(): void
    {
        $compiler = new PieCompiler($this->views, $this->cache);
        $compiled = $compiler->compileSource(
            '@auth signed-in @endauth' .
            '@isflash("notice")notice@endisflash' .
            '@csrf @flash("notice") @error("email") @old("email")' .
            '@css("app") @js("app")'
        );

        self::assertStringContainsString('session("user", false)', $compiled);
        self::assertStringContainsString('is_flash("notice")', $compiled);
        self::assertStringContainsString('csrf_token()', $compiled);
        self::assertStringContainsString('flash("notice")', $compiled);
        self::assertStringContainsString('error_', $compiled);
        self::assertStringContainsString('old("email")', $compiled);
        self::assertStringContainsString('css_source("app")', $compiled);
        self::assertStringContainsString('js_source("app")', $compiled);
    }

    public function testSupportsNestedComponentLibraryImports(): void
    {
        mkdir($this->views . '/components', 0777, true);
        file_put_contents(
            $this->views . '/components/label.pie.php',
            "@pie('text'){{ \$label }}@endpie"
        );
        file_put_contents(
            $this->views . '/components/button.pie.php',
            "@import('components.label', 'label')@pie('primary')<button>@label('text')</button>@endpie"
        );
        file_put_contents(
            $this->views . '/nested-component-page.pie.php',
            "@import('components.button', 'ui')@ui('primary')"
        );

        $html = (new ViewRenderer($this->views, $this->cache))->render(
            'nested-component-page',
            ['label' => 'Submit']
        );

        self::assertSame('<button>Submit</button>', $html);
    }

    public function testRendersComponentAttributesAndDefaultSlot(): void
    {
        mkdir($this->views . '/components', 0777, true);
        file_put_contents(
            $this->views . '/components/alert.pie.php',
            '<aside class="alert {{ $type }}">{{ $message }}<x-slot /></aside>'
        );
        file_put_contents(
            $this->views . '/component-attributes.pie.php',
            '<x-alert type="success" :message="$message">Saved</x-alert>'
        );

        $html = (new ViewRenderer($this->views, $this->cache))->render(
            'component-attributes',
            ['message' => 'Done']
        );

        self::assertSame('<aside class="alert success">DoneSaved</aside>', $html);
    }

    public function testRendersNamedSlotsAndComponentFallbacks(): void
    {
        mkdir($this->views . '/components', 0777, true);
        file_put_contents(
            $this->views . '/components/panel.pie.php',
            '<section><header><x-slot name="title">Default title</x-slot></header><main><x-slot /></main></section>'
        );
        file_put_contents(
            $this->views . '/named-slots.pie.php',
            '<x-panel><x-slot name="title">Custom title</x-slot>Panel body</x-panel><x-panel>Only body</x-panel>'
        );

        $html = (new ViewRenderer($this->views, $this->cache))->render('named-slots');

        self::assertSame(
            '<section><header>Custom title</header><main>Panel body</main></section>'
                . '<section><header>Default title</header><main>Only body</main></section>',
            $html
        );
    }

    public function testSupportsNestedComponentsAndCallerScopedSlotVariables(): void
    {
        mkdir($this->views . '/components', 0777, true);
        file_put_contents(
            $this->views . '/components/badge.pie.php',
            '<b>{{ $label }}</b>'
        );
        file_put_contents(
            $this->views . '/components/card.pie.php',
            '<article><x-slot /></article>'
        );
        file_put_contents(
            $this->views . '/nested-components.pie.php',
            '<x-card><x-badge :label="$name" /></x-card>'
        );

        $html = (new ViewRenderer($this->views, $this->cache))->render(
            'nested-components',
            ['name' => 'Nested']
        );

        self::assertSame('<article><b>Nested</b></article>', $html);
    }

    public function testComponentScopeDoesNotLeakBetweenComponents(): void
    {
        mkdir($this->views . '/components', 0777, true);
        file_put_contents(
            $this->views . '/components/greeting.pie.php',
            '<p>{{ $message }}</p>'
        );
        file_put_contents(
            $this->views . '/isolated-components.pie.php',
            '<x-greeting message="First" /><x-greeting message="Second" />'
        );

        $html = (new ViewRenderer($this->views, $this->cache))->render('isolated-components');

        self::assertSame('<p>First</p><p>Second</p>', $html);
    }

    public function testInvalidatesCompiledViewWhenLayoutChanges(): void
    {
        file_put_contents(
            $this->views . '/layouts/cacheable.pie.php',
            '<main>@slot("content")</main>'
        );
        file_put_contents(
            $this->views . '/pages/cacheable.pie.php',
            "@use('layouts.cacheable')@part('content')First@endpart"
        );

        $renderer = new ViewRenderer($this->views, $this->cache);
        self::assertSame('<main>First</main>', $renderer->render('pages.cacheable'));

        file_put_contents(
            $this->views . '/layouts/cacheable.pie.php',
            '<section>@slot("content")</section>'
        );

        self::assertSame('<section>First</section>', $renderer->render('pages.cacheable'));
    }

    public function testInvalidatesCompiledViewWhenImportedComponentChanges(): void
    {
        mkdir($this->views . '/components', 0777, true);
        file_put_contents(
            $this->views . '/components/cacheable.pie.php',
            "@pie('label')<b>Old</b>@endpie"
        );
        file_put_contents(
            $this->views . '/component-cache.pie.php',
            "@import('components.cacheable', 'ui')@ui('label')"
        );

        $renderer = new ViewRenderer($this->views, $this->cache);
        self::assertSame('<b>Old</b>', $renderer->render('component-cache'));

        file_put_contents(
            $this->views . '/components/cacheable.pie.php',
            "@pie('label')<strong>New</strong>@endpie"
        );

        self::assertSame('<strong>New</strong>', $renderer->render('component-cache'));
    }

    public function testInvalidatesCompiledViewWhenNestedComponentChanges(): void
    {
        mkdir($this->views . '/components', 0777, true);
        file_put_contents($this->views . '/components/badge.pie.php', '<b>Old badge</b>');
        file_put_contents($this->views . '/components/card.pie.php', '<article><x-badge /></article>');
        file_put_contents($this->views . '/nested-component-cache.pie.php', '<x-card />');

        $renderer = new ViewRenderer($this->views, $this->cache);
        self::assertSame('<article><b>Old badge</b></article>', $renderer->render('nested-component-cache'));

        file_put_contents($this->views . '/components/badge.pie.php', '<strong>New badge</strong>');

        self::assertSame('<article><strong>New badge</strong></article>', $renderer->render('nested-component-cache'));
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
