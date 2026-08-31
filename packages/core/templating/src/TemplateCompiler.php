<?php

declare(strict_types=1);

namespace PhpModern\Templating;

/**
 * Compiles template source into plain PHP — a pure string-in/string-out
 * function, deliberately not eval()'d anywhere: View writes the compiled
 * result to a cache file and `include`s it, the same reason Blade compiles
 * to disk instead of eval().
 *
 * Convention: each directive sits alone on its own line. The parenthesized
 * directives (@if/@elseif/@foreach/@include/@section/@yield/@extends) match
 * greedily to the last `)` on that line, so a condition/argument list may
 * itself contain nested parens (`@if(count($items) > 0)`) — but two
 * directives sharing one line is not supported. A DSL simple enough to
 * implement in an afternoon is not going to have Blade's full parser.
 */
final class TemplateCompiler
{
    public function compile(string $source): string
    {
        $source = $this->compileRawEchoes($source);
        $source = $this->compileEscapedEchoes($source);

        return $this->compileDirectives($source);
    }

    private function compileRawEchoes(string $source): string
    {
        return (string) preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?= $1 ?>', $source);
    }

    private function compileEscapedEchoes(string $source): string
    {
        return (string) preg_replace(
            '/\{\{\s*(.+?)\s*\}\}/',
            '<?= htmlspecialchars((string) ($1), ENT_QUOTES, \'UTF-8\') ?>',
            $source,
        );
    }

    private function compileDirectives(string $source): string
    {
        $replacements = [
            '/@extends\((.+)\)/' => '<?php $__view->extend($1); ?>',
            '/@include\((.+)\)/' => '<?= $__view->renderInclude($1) ?>',
            '/@section\((.+)\)/' => '<?php $__view->startSection($1); ?>',
            '/@endsection\b/' => '<?php $__view->endSection(); ?>',
            '/@yield\((.+)\)/' => '<?= $__view->yieldSection($1) ?>',
            '/@if\((.+)\)/' => '<?php if ($1): ?>',
            '/@elseif\((.+)\)/' => '<?php elseif ($1): ?>',
            '/@else\b/' => '<?php else: ?>',
            '/@endif\b/' => '<?php endif; ?>',
            '/@foreach\((.+)\)/' => '<?php foreach ($1): ?>',
            '/@endforeach\b/' => '<?php endforeach; ?>',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $source = (string) preg_replace($pattern, $replacement, $source);
        }

        return $source;
    }
}
