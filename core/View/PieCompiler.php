<?php

declare(strict_types=1);

namespace Ds\View;

use RuntimeException;

final class PieCompiler
{
    private const VERSION = '5';

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

        // A view's output depends on its layouts and imported components too.
        // Include their content fingerprints so persistent workers never serve stale
        // compiled templates after a partial/layout is deployed.
        $dependencies = $this->dependencyFingerprint($source, $filename);
        $key = hash('sha256', self::VERSION . "\0" . $filename . "\0" . $source . "\0" . $dependencies);
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
        $source = $this->expandImports($source, $origin, 0);
        $source = $this->expandComponents($source, $origin, 0);
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

    private function expandImports(string $source, string $origin, int $depth): string
    {
        if ($depth > 16) {
            throw new RuntimeException("PieTemplate import nesting exceeded 16 levels in {$origin}.");
        }

        if (preg_match_all(
            '/@import\(\s*([\'"])([^\'"]+)\1\s*,\s*([\'"])([A-Za-z_][A-Za-z0-9_]*)\3\s*\)/',
            $source,
            $imports,
            PREG_SET_ORDER
        ) === false) {
            return $source;
        }

        foreach ($imports as $import) {
            $filename = $this->resolveView($import[2]);
            $componentSource = file_get_contents($filename);
            if ($componentSource === false) {
                throw new RuntimeException("Unable to read PieTemplate import: {$filename}");
            }

            // Resolve imports declared by a component file before extracting its
            // named snippets, so component libraries can be composed from smaller
            // component libraries without exposing their imports to the page.
            $componentSource = $this->expandImports($componentSource, $filename, $depth + 1);

            if (preg_match_all(
                '/@pie\(\s*([\'"])(.*?)\1\s*\)(.*?)@endpie/s',
                $componentSource,
                $definitions,
                PREG_SET_ORDER
            ) === false) {
                throw new RuntimeException("Unable to parse PieTemplate import: {$filename}");
            }

            $components = [];
            foreach ($definitions as $definition) {
                $components[$definition[2]] = $definition[3];
            }

            $alias = $import[4];
            $pattern = '/@' . preg_quote($alias, '/') . '\(\s*([\'"])(.*?)\1\s*\)/s';
            $source = preg_replace_callback(
                $pattern,
                static function (array $match) use ($components, $alias, $filename): string {
                    if (!array_key_exists($match[2], $components)) {
                        throw new RuntimeException(
                            "PieTemplate component [{$alias}({$match[2]})] was not found in {$filename}."
                        );
                    }

                    return $components[$match[2]];
                },
                $source
            ) ?? $source;

            $source = str_replace($import[0], '', $source);
        }

        return $source;
    }

    /**
     * Expand <x-name> component nodes into isolated PHP rendering scopes.
     * Slots are closures so their expressions execute with the caller's data,
     * while component attributes are extracted only inside the component scope.
     */
    private function expandComponents(string $source, string $origin, int $depth): string
    {
        if ($depth > 32) {
            throw new RuntimeException("PieTemplate component nesting exceeded 32 levels in {$origin}.");
        }

        $pattern = '/<\/?x-[A-Za-z0-9_.-]+\b[^>]*>/s';
        $offset = 0;

        while (preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $tagText = $match[0][0];
            $start = $match[0][1];
            $end = $start + strlen($tagText);
            if (preg_match('/^<\/x-([A-Za-z0-9_.-]+)\s*>$/i', $tagText) === 1) {
                throw new RuntimeException("Unexpected closing PieTemplate component tag in {$origin}: {$tagText}");
            }

            if (preg_match('/^<x-([A-Za-z0-9_.-]+)\b(.*?)>$/is', $tagText, $opening) !== 1) {
                $offset = $end;
                continue;
            }

            $tagName = $opening[1];
            $attributesText = $opening[2];
            $selfClosing = preg_match('/\/\s*>$/', $tagText) === 1;
            if ($selfClosing) {
                $inner = '';
                $nodeEnd = $end;
            } else {
                $closing = $this->findComponentClosingTag($source, $tagName, $end);
                if ($closing === null) {
                    throw new RuntimeException("Unclosed PieTemplate component <x-{$tagName}> in {$origin}.");
                }
                $inner = substr($source, $end, $closing[0] - $end);
                $nodeEnd = $closing[1];
            }

            if (strtolower($tagName) === 'slot') {
                // x-slot nodes are meaningful only inside a component template.
                $offset = $nodeEnd;
                continue;
            }

            $inner = $this->expandComponents($inner, $origin, $depth + 1);
            [$defaultSlot, $namedSlots] = $this->extractComponentSlots($inner, $origin);
            $viewName = 'components.' . str_replace('/', '.', $tagName);
            $filename = $this->resolveView($viewName);
            $componentSource = file_get_contents($filename);
            if ($componentSource === false) {
                throw new RuntimeException("Unable to read PieTemplate component: {$filename}");
            }

            $componentSource = $this->expandLayouts($componentSource, $filename, 0);
            $componentSource = $this->applySlots($componentSource);
            $componentSource = $this->expandImports($componentSource, $filename, 0);
            $componentSource = $this->replaceComponentSlots($componentSource, $filename);
            $componentSource = $this->expandComponents($componentSource, $filename, $depth + 1);

            $attributes = $this->parseComponentAttributes($attributesText);
            $contextVariable = '$__pie_context_' . substr(hash('sha256', $origin . ':' . $start . ':' . $tagName), 0, 10);
            $attributeCode = [];
            foreach ($attributes as $name => $value) {
                $attributeCode[] = var_export($name, true) . ' => ' . $value;
            }

            $slotCode = [
                "'default' => " . $this->slotClosure($defaultSlot, $contextVariable),
            ];
            foreach ($namedSlots as $name => $content) {
                $slotCode[] = var_export($name, true) . ' => ' . $this->slotClosure($content, $contextVariable);
            }

            $replacement = '<?php ' . $contextVariable . ' = get_defined_vars(); '
                . '(static function (array $__pie_parent, array $__pie_attrs, array $__pie_slots): void { '
                . 'extract($__pie_parent, EXTR_SKIP); extract($__pie_attrs, EXTR_OVERWRITE); ?>'
                . $componentSource
                . '<?php })( ' . $contextVariable . ', [' . implode(', ', $attributeCode) . '], ['
                . implode(', ', $slotCode) . ']); unset(' . $contextVariable . '); ?>';

            $source = substr_replace($source, $replacement, $start, $nodeEnd - $start);
            $offset = $start + strlen($replacement);
        }

        return $source;
    }

    /**
     * Find a balanced closing component tag, accounting for nested tags with
     * the same name. Returns [closing-tag start, closing-tag end].
     */
    private function findComponentClosingTag(string $source, string $tagName, int $offset): ?array
    {
        $pattern = '/<\/?x-' . preg_quote($tagName, '/') . '\b[^>]*>/is';
        $depth = 1;
        while (preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $text = $match[0][0];
            $start = $match[0][1];
            $offset = $start + strlen($text);
            if (preg_match('/^<\/x-/i', $text) === 1) {
                if (--$depth === 0) {
                    return [$start, $offset];
                }
            } elseif (preg_match('/\/\s*>$/', $text) !== 1) {
                $depth++;
            }
        }

        return null;
    }

    /**
     * Parse named x-slot children. Remaining markup becomes the default slot.
     *
     * @return array{0:string,1:array<string,string>}
     */
    private function extractComponentSlots(string $content, string $origin): array
    {
        $slots = [];
        $pattern = '/<x-slot\b([^>]*)>/is';
        $offset = 0;
        while (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $tag = $match[0][0];
            $start = $match[0][1];
            $tagEnd = $start + strlen($tag);
            $attrs = $this->parseComponentAttributes($match[1][0]);
            $nameValue = $attrs['name'] ?? "'default'";
            if (!is_string($nameValue) || !str_starts_with($nameValue, "'")) {
                throw new RuntimeException("PieTemplate x-slot name must be a static string in {$origin}.");
            }
            $name = stripcslashes(substr($nameValue, 1, -1));
            $selfClosing = preg_match('/\/\s*>$/', $tag) === 1;
            if ($selfClosing) {
                $body = '';
                $nodeEnd = $tagEnd;
            } else {
                $closing = $this->findComponentClosingTag($content, 'slot', $tagEnd);
                if ($closing === null) {
                    throw new RuntimeException("Unclosed x-slot element in {$origin}.");
                }
                $body = substr($content, $tagEnd, $closing[0] - $tagEnd);
                $nodeEnd = $closing[1];
            }

            if (array_key_exists($name, $slots)) {
                throw new RuntimeException("Duplicate PieTemplate slot [{$name}] in {$origin}.");
            }
            $slots[$name] = $body;
            $content = substr_replace($content, '', $start, $nodeEnd - $start);
            $offset = $start;
        }

        return [$content, $slots];
    }

    /**
     * Replace x-slot placeholders inside component templates. Fallback content
     * is retained and rendered when the caller did not provide that slot.
     */
    private function replaceComponentSlots(string $source, string $origin): string
    {
        $pattern = '/<x-slot\b([^>]*)>/is';
        $offset = 0;
        while (preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $tag = $match[0][0];
            $start = $match[0][1];
            $tagEnd = $start + strlen($tag);
            $attrs = $this->parseComponentAttributes($match[1][0]);
            $nameExpression = $attrs['name'] ?? "'default'";
            if (!is_string($nameExpression) || !str_starts_with($nameExpression, "'")) {
                throw new RuntimeException("PieTemplate x-slot name must be a static string in {$origin}.");
            }
            $name = stripcslashes(substr($nameExpression, 1, -1));
            $selfClosing = preg_match('/\/\s*>$/', $tag) === 1;
            if ($selfClosing) {
                $fallback = '';
                $nodeEnd = $tagEnd;
            } else {
                $closing = $this->findComponentClosingTag($source, 'slot', $tagEnd);
                if ($closing === null) {
                    throw new RuntimeException("Unclosed x-slot placeholder in {$origin}.");
                }
                $fallback = substr($source, $tagEnd, $closing[0] - $tagEnd);
                $nodeEnd = $closing[1];
            }

            $key = var_export($name, true);
            $replacement = '<?php if (isset($__pie_slots[' . $key . '])) { echo $__pie_slots[' . $key . '](); } else { ?>'
                . $fallback . '<?php } ?>';
            $source = substr_replace($source, $replacement, $start, $nodeEnd - $start);
            $offset = $start + strlen($replacement);
        }

        return $source;
    }

    /**
     * Return PHP expressions keyed by component attribute name.
     * :foo="$value" is evaluated as PHP; ordinary attributes are strings.
     */
    private function parseComponentAttributes(string $source): array
    {
        $attributes = [];
        $pattern = '/(:?[A-Za-z_][A-Za-z0-9_.:-]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+)))?/';
        if (preg_match_all($pattern, trim($source), $matches, PREG_SET_ORDER) === false) {
            return $attributes;
        }

        foreach ($matches as $match) {
            $name = $match[1];
            $hasValue = isset($match[2]) || isset($match[3]) || isset($match[4]);
            $value = $match[2] ?? $match[3] ?? $match[4] ?? null;
            if (str_starts_with($name, ':')) {
                if (!$hasValue || trim((string) $value) === '') {
                    throw new RuntimeException("Dynamic PieTemplate attribute [{$name}] requires a PHP expression.");
                }
                $attributes[substr($name, 1)] = '(' . $value . ')';
            } else {
                $attributes[$name] = var_export($hasValue ? $value : true, true);
            }
        }

        return $attributes;
    }

    private function slotClosure(string $content, string $contextVariable): string
    {
        return 'static function () use (' . $contextVariable . ') { '
            . 'extract(' . $contextVariable . ', EXTR_SKIP); ob_start(); ?>'
            . $content
            . '<?php return (string) ob_get_clean(); }';
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
            'elseif' => static fn (string $e): string => '<?php elseif (' . $e . '): ?>',
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
        $directivePattern = '/@(elseif|if|foreach|for|while|isset|notset|empty|notempty|isnull|notnull|switch|isflash|flash|error|old|css|js|case)\s*\(/';

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
            '/@else(?![A-Za-z_]):?/' => '<?php else: ?>',
            '/@endif\b/' => '<?php endif; ?>',
            '/@endforeach\b/' => '<?php endforeach; ?>',
            '/@endfor\b/' => '<?php endfor; ?>',
            '/@endwhile\b/' => '<?php endwhile; ?>',
            '/@endisset\b|@endnotset\b|@endempty\b|@endnotempty\b|@endisnull\b|@endnotnull\b|@endisflash\b|@endauth\b/' => '<?php endif; ?>',
            '/@auth\b/' => '<?php if (session("user", false)): ?>',
            '/@csrf\b/' => '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?>">',
            '/@default(?![A-Za-z_]):?/' => '<?php default: ?>',
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

    /**
     * Fingerprint every file that can affect this template's compiled output.
     * The visited set also prevents circular layout/import references from
     * causing unbounded recursion while still allowing the compiler to report
     * the actual cycle/expansion error in the normal compilation path.
     */
    private function dependencyFingerprint(string $source, string $origin, array &$visited = []): string
    {
        $realOrigin = realpath($origin);
        if ($realOrigin !== false) {
            if (isset($visited[$realOrigin])) {
                return '';
            }
            $visited[$realOrigin] = hash('sha256', $source);
        }

        $references = [];
        if (preg_match_all(
            '/@(?:use|import)\(\s*([\'"])([^\'"]+)\1/',
            $source,
            $matches
        ) !== false) {
            $references = array_values(array_unique($matches[2]));
        }

        sort($references);
        foreach ($references as $reference) {
            $filename = $this->resolveView($reference);
            $dependencySource = file_get_contents($filename);
            if ($dependencySource === false) {
                throw new RuntimeException("Unable to read PieTemplate dependency: {$filename}");
            }

            $this->dependencyFingerprint($dependencySource, $filename, $visited);
        }

        ksort($visited);
        return hash('sha256', serialize($visited));
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
