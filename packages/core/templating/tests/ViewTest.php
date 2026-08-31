<?php

declare(strict_types=1);

namespace PhpModern\Templating\Tests;

use PhpModern\Templating\View;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewTest extends TestCase
{
    private string $viewsDir;
    private string $cacheDir;
    private View $view;

    protected function setUp(): void
    {
        $unique = uniqid('', true);
        $this->viewsDir = sys_get_temp_dir() . '/phpmodern-views-' . $unique;
        $this->cacheDir = sys_get_temp_dir() . '/phpmodern-view-cache-' . $unique;
        mkdir($this->viewsDir, 0777, true);

        $this->view = new View($this->viewsDir, $this->cacheDir);
    }

    protected function tearDown(): void
    {
        self::removeDir($this->viewsDir);
        self::removeDir($this->cacheDir);
    }

    public function test_escaped_echo_escapes_html_in_the_given_data(): void
    {
        $this->writeView('greeting', '<p>Hello, {{ $name }}!</p>');

        $output = $this->view->render('greeting', ['name' => '<script>alert(1)</script>']);

        self::assertSame('<p>Hello, &lt;script&gt;alert(1)&lt;/script&gt;!</p>', $output);
    }

    public function test_raw_echo_does_not_escape(): void
    {
        $this->writeView('raw', '{!! $html !!}');

        $output = $this->view->render('raw', ['html' => '<b>bold</b>']);

        self::assertSame('<b>bold</b>', $output);
    }

    public function test_if_and_foreach_actually_execute(): void
    {
        $this->writeView('list', implode("\n", [
            '@if(count($items) > 0)',
            '<ul>',
            '@foreach($items as $item)',
            '<li>{{ $item }}</li>',
            '@endforeach',
            '</ul>',
            '@else',
            '<p>empty</p>',
            '@endif',
        ]));

        $withItems = $this->view->render('list', ['items' => ['a', 'b']]);
        self::assertSame("<ul>\n<li>a</li>\n<li>b</li>\n</ul>\n", $withItems);

        $withoutItems = $this->view->render('list', ['items' => []]);
        self::assertSame("<p>empty</p>\n", $withoutItems);
    }

    public function test_include_composes_another_view_with_its_own_data(): void
    {
        $this->writeView('partials/badge', '<span>{{ $label }}</span>');
        $this->writeView('page', "@include('partials.badge', ['label' => 'new'])");

        $output = $this->view->render('page');

        self::assertSame('<span>new</span>', $output);
    }

    public function test_extends_and_yield_pull_the_childs_sections_into_the_layout(): void
    {
        $this->writeView('layout', implode("\n", [
            '<html><body>',
            '<h1>@yield('."'title', 'Untitled'".')</h1>',
            '<main>@yield('."'content'".')</main>',
            '</body></html>',
        ]));

        $this->writeView('page-with-layout', implode("\n", [
            "@extends('layout')",
            "@section('title')",
            'My Page',
            '@endsection',
            "@section('content')",
            '<p>Body text</p>',
            '@endsection',
        ]));

        $output = $this->view->render('page-with-layout');

        self::assertSame(
            "<html><body>\n<h1>My Page\n</h1>\n<main><p>Body text</p>\n</main>\n</body></html>",
            $output,
        );
    }

    public function test_yield_falls_back_to_its_default_when_the_child_never_defined_that_section(): void
    {
        $this->writeView('layout-with-default', "<h1>@yield('title', 'Default Title')</h1>");
        $this->writeView('child-without-title', "@extends('layout-with-default')");

        $output = $this->view->render('child-without-title');

        self::assertSame('<h1>Default Title</h1>', $output);
    }

    public function test_recompiles_when_the_source_file_changes(): void
    {
        $this->writeView('changeable', 'version one');
        self::assertSame('version one', $this->view->render('changeable'));

        // Force a distinct, later mtime — some filesystems have 1s mtime
        // resolution, so writing again immediately could keep the same
        // timestamp and hide a real cache-invalidation bug.
        $path = $this->viewsDir . '/changeable.phtml';
        file_put_contents($path, 'version two');
        touch($path, time() + 5);

        self::assertSame('version two', $this->view->render('changeable'));
    }

    public function test_a_missing_view_throws_a_clear_error(): void
    {
        $this->expectException(RuntimeException::class);

        $this->view->render('does-not-exist');
    }

    private function writeView(string $name, string $contents): void
    {
        $path = $this->viewsDir . '/' . str_replace('.', '/', $name) . '.phtml';
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $contents);
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = "{$dir}/{$entry}";
            is_dir($path) ? self::removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
