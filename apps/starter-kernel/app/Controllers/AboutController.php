<?php

declare(strict_types=1);

namespace App\Controllers;

use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\I18n\Translator;
use PhpModern\Templating\View;

/**
 * Renders resources/views/about.phtml through phpmodern/templating —
 * layout inheritance, partial composition — with its copy pulled through
 * phpmodern/i18n's Translator instead of hardcoded strings. ?lang=pt-br
 * switches locale; anything else (or nothing) falls back to Translator's
 * own default locale.
 */
final class AboutController
{
    public function __construct(
        private readonly View $view,
        private readonly Translator $translator,
    ) {
    }

    /** @param array<string, string> $params */
    public function show(Request $request, array $params): Response
    {
        $locale = $request->query['lang'] ?? null;
        $engine = htmlspecialchars('phpmodern/templating', ENT_QUOTES, 'UTF-8');

        $html = $this->view->render('about', [
            'title' => $this->translator->translate('about.title', locale: $locale),
            'heading' => $this->translator->translate('about.heading', locale: $locale),
            'intro' => $this->translator->translate(
                'about.rendered_by',
                ['engine' => "<strong>{$engine}</strong>"],
                $locale,
            ),
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
