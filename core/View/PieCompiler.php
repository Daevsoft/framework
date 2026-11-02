<?php

declare(strict_types=1);

namespace Ds\View;

use RuntimeException;

final class PieCompiler
{
    private const VERSION = '2';

    public function __construct(
        private readonly string $viewRoot,
        private readonly string $cacheRoot
    ) {
    }

    public function compile(string $filename): string
    {
        $source = file_get_contents($filename);
        if ($source === false) {
            throw new RuntimeException("Unable to read view: {$filename}");
        }

        $key = hash('sha256', self::VERSION . "\0" . $filename . "\0" . $source);
        $target = rtrim($this->cacheRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.php';

        if (is_file($target)) {
            return $target;
        }

        $compiled = $this->compileSource($source, $filename);
        $directory = dirname($target);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create compiled view directory: {$directory}");
        }

        $temporary = $target . '.' . getmypid() . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($temporary, $compiled, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write compiled view: {$target}");
        }

        if (!@rename($temporary, $target)) {
            @unlink($temporary);
            if (!is_file($target)) {
                throw new RuntimeException("Unable to publish compiled view: {$target}");
            }
        }

        return $target;
    }

    public function compileSource(string $source, string $origin = '<string>'): string
    {
        $source = $this->expandLayouts($source, $origin, 0);
        $source = $this->applySlots($source);
        $source = $this->compileEchoStatements($source);
        return $this->compileDirectives($source);
    }

    private function expandLayouts(string $source, string $origin, int $depth): string
    {
        if ($depth > 16) {
            throw new RuntimeException("PieTemplate layout nesting exceeded 16 levels in {$origin}.");
        }

        if (preg_match_all('/@use\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $source, $matches) === 0) {
            return $source;
        }

        foreach ($matches[0] as $index => $directive) {
            $layout = $this->resolveView($matches[1][$index]);
            $layoutSource = file_get_contents($layout);
            if ($layoutSource === false) {
                throw new RuntimeException("Unable to read PieTemplate layout: {$layout}");
            }

            $layoutSource = $this->expandLayouts($layoutSource, $layout, $depth + 1);
            $source = str_replace($directive, $layoutSource, $source);
        }

        return $source;
    }

    private function applySlots(string $source): string
    {
        if (preg_match_all(
            '/@part\(\s*[\'"]([^\'"]+)[\'"]\s*\)(.*?)@endpart/s',
            $source,
            $parts,
            PREG_SET_ORDER
        ) === false) {
            return $source;
        }

        foreach ($parts as $part) {
            $slotPattern = '/@slot\(\s*[\'"]' . preg_quote($part[1], '/') . '[\'"]\s*\)/';
            $source = preg_replace($slotPattern, $part[2], $source) ?? $source;
            $source = str_replace($part[0], '', $source);
        }

        return preg_replace('/@slot\(\s*[\'"][^\'"]+[\'"]\s*\)/', '', $source) ?? $source;
    }

    private function compileEchoStatements(string $source): string
    {
        $source = preg_replace_callback(
            '/(?<!@)\{\{\s*(.*?)\s*\}\}/s',
            static fn (array $m): string => '<?= (' . $m[1] . ') ?>',
            $source
        ) ?? $source;

        $source = preg_replace_callback(
            '/\{!\s*(.*?)\s*\!\}/s',
            static fn (array $m): string => '<?= htmlspecialchars((string) (' . $m[1] . '), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?>',
            $source
        ) ?? $source;

        $source = preg_replace_callback(
            '/<<\s*(.*?)\s*>>/s',
            static fn (array $m): string => '<?php ' . $m[1] . ' ?>',
            $source
        ) ?? $source;

        return str_replace('@{{', '{{', $source);
    }

    private function compileDirectives(string $source): string
    {
        $replacements = [
            'elseif' => static fn (string $e): string => '<?php } elseif (' . $e . '): ?>',
            'if' => static fn (string $e): string => '<?php if (' . $e . '): ?>',
            'foreach' => static fn (string $e): string => '<?php foreach (' . $e . '): ?>',
            'for' => static fn (string $e): string => '<?php for (' . $e . '): ?>',
            'while' => static fn (string $e): string => '<?php while (' . $e . '): ?>',
            'isset' => static fn (string $e): string => '<?php if (isset(' . $e . ')): ?>',
            'notset' => static fn (string $e): string => '<?php if (!isset(' . $e . ')): ?>',
            'empty' => static fn (string $e): string => '<?php if (empty(' . $e . ')): ?>',
            'notempty' => static fn (string $e): string => '<?php if (!empty(' . $e . ')): ?>',
            'isnull' => static fn (string $e): string => '<?php if ((' . $e . ') === null): ?>',
            'notnull' => static fn (string $e): string => '<?php if ((' . $e . ') !== null): ?>',
            'switch' => static fn (string $e): string => '<?php switch (' . $e . '): ?>',
            'isflash' => static fn (string $e): string => '<?php if (is_flash(' . $e . ')): ?>',
            'flash' => static fn (string $e): string => '<?= flash(' . $e . ') ?>',
            'error' => static fn (string $e): string => '<?= flash("error_" . (' . $e . ')) ?>',
            'old' => static fn (string $e): string => '<?= old(' . $e . ') ?>',
            'css' => static fn (string $e): string => '<?= css_source(' . $e . ') ?>',
            'js' => static fn (string $e): string => '<?= js_source(' . $e . ') ?>',
            'case' => static fn (string $e): string => '<?php case ' . $e . ': ?>',
        ];

        $offset = 0;
        $directivePattern = '/@(elseif|if|foreach|for|while|isset|notset|empty|notempty|isnull|notnull|switch|case)\s*\(/';

        while (preg_match($directivePattern, $source, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $directive = $match[1][0];
            $start = $match[0][1];
            $open = $start + strlen($match[0][0]) - 1;
            $close = $this->findClosingParenthesis($source, $open);

            if ($close === null) {
                $offset = $open + 1;
                continue;
            }

            $expression = trim(substr($source, $open + 1, $close - $open - 1));
            $end = $close + 1;
            if (($source[$end] ?? '') === ':') {
                $end++;
            }

            $replacement = $replacements[$directive]($expression);
            $source = substr_replace($source, $replacement, $start, $end - $start);
            $offset = $start + strlen($replacement);
        }

        // Also accept the original line-oriented @case expression syntax.
        $source = preg_replace_callback(
            '/@case[ \t]+([^\r\n]+)/',
            static function (array $m): string {
                $expression = rtrim(trim($m[1]), ':');
                return '<?php case ' . $expression . ': ?>';
            },
            $source
        ) ?? $source;

        $simple = [
            '/@else:?(?![A-Za-z_])/' => '<?php else: ?>',
            '/@endif\b/' => '<?php endif; ?>',
            '/@endforeach\b/' => '<?php endforeach; ?>',
            '/@endfor\b/' => '<?php endfor; ?>',
            '/@endwhile\b/' => '<?php endwhile; ?>',
            '/@endisset\b|@endnotset\b|@endempty\b|@endnotempty\b|@endisnull\b|@endnotnull\b/' => '<?php endif; ?>',
            '/@default\b/' => '<?php default: ?>',
            '/@break\b/' => '<?php break; ?>',
            '/@continue\b/' => '<?php continue; ?>',
            '/@endswitch\b/' => '<?php endswitch; ?>',
        ];

        foreach ($simple as $pattern => $replacement) {
            $source = preg_replace($pattern, $replacement, $source) ?? $source;
        }

        return preg_replace_callback(
            '/@join\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            static fn (array $m): string => '<?= view(' . var_export($m[1], true) . ') ?>',
            $source
        ) ?? $source;
    }

    private function findClosingParenthesis(string $source, int $open): ?int
    {
        $depth = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = $open; $index < $length; $index++) {
            $char = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
            } elseif ($char === '(') {
                $depth++;
            } elseif ($char === ')' && --$depth === 0) {
                return $index;
            }
        }

        return null;
    }

    private function resolveView(string $name): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, trim($name));
        if ($relative === '' || str_contains($relative, "\0")) {
            throw new RuntimeException('Invalid PieTemplate view name.');
        }

        $filename = rtrim($this->viewRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relative . '.pie.php';
        $real = realpath($filename);
        $root = realpath($this->viewRoot);

        if ($real === false || $root === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException("PieTemplate layout [{$name}] was not found inside the view directory.");
        }

        return $real;
    }
}
