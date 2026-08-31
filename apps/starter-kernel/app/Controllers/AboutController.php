<?php

declare(strict_types=1);

namespace App\Controllers;

use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\Templating\View;

/**
 * Renders resources/views/about.phtml through phpmodern/templating instead
 * of a heredoc string — real layout inheritance (@extends/@section/@yield)
 * and partial composition (@include), not just an echo directive.
 */
final class AboutController
{
    public function __construct(private readonly View $view)
    {
    }

    /** @param array<string, string> $params */
    public function show(Request $request, array $params): Response
    {
        $html = $this->view->render('about', [
            'engine' => 'phpmodern/templating',
            'renderedAt' => date('c'),
            'features' => [
                'Layouts (@extends/@section/@yield)',
                'Partials (@include)',
                'Escaped and raw echoes',
                'Compiled to cached PHP files, not eval()',
            ],
        ]);

        return Response::html($html);
    }
}
