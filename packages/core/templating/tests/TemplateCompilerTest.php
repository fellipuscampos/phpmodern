<?php

declare(strict_types=1);

namespace PhpModern\Templating\Tests;

use PhpModern\Templating\TemplateCompiler;
use PHPUnit\Framework\TestCase;

final class TemplateCompilerTest extends TestCase
{
    private TemplateCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new TemplateCompiler();
    }

    public function test_escaped_echo_compiles_to_htmlspecialchars(): void
    {
        $compiled = $this->compiler->compile('{{ $name }}');

        self::assertSame('<?= htmlspecialchars((string) ($name), ENT_QUOTES, \'UTF-8\') ?>', $compiled);
    }

    public function test_raw_echo_compiles_without_escaping(): void
    {
        $compiled = $this->compiler->compile('{!! $html !!}');

        self::assertSame('<?= $html ?>', $compiled);
    }

    public function test_if_directive_compiles_and_supports_nested_parens_in_the_condition(): void
    {
        $compiled = $this->compiler->compile("@if(count(\$items) > 0)\ngot items\n@endif");

        self::assertStringContainsString('<?php if (count($items) > 0): ?>', $compiled);
        self::assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function test_if_elseif_else_endif(): void
    {
        $compiled = $this->compiler->compile("@if(\$a)\nA\n@elseif(\$b)\nB\n@else\nC\n@endif");

        self::assertStringContainsString('<?php if ($a): ?>', $compiled);
        self::assertStringContainsString('<?php elseif ($b): ?>', $compiled);
        self::assertStringContainsString('<?php else: ?>', $compiled);
        self::assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function test_foreach_directive(): void
    {
        $compiled = $this->compiler->compile("@foreach(\$items as \$item)\n{{ \$item }}\n@endforeach");

        self::assertStringContainsString('<?php foreach ($items as $item): ?>', $compiled);
        self::assertStringContainsString('<?php endforeach; ?>', $compiled);
    }

    public function test_extends_section_endsection_yield(): void
    {
        $compiled = $this->compiler->compile("@extends('layout')\n@section('title')\nHello\n@endsection");

        self::assertStringContainsString("<?php \$__view->extend('layout'); ?>", $compiled);
        self::assertStringContainsString("<?php \$__view->startSection('title'); ?>", $compiled);
        self::assertStringContainsString('<?php $__view->endSection(); ?>', $compiled);

        $withYield = $this->compiler->compile("@yield('title')");
        self::assertSame("<?= \$__view->yieldSection('title') ?>", $withYield);
    }

    public function test_include_directive_passes_through_its_arguments(): void
    {
        $compiled = $this->compiler->compile("@include('partials.header', ['title' => 'Home'])");

        self::assertSame("<?= \$__view->renderInclude('partials.header', ['title' => 'Home']) ?>", $compiled);
    }
}
