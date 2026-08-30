<?php

declare(strict_types=1);

/**
 * File-based routing demo: this file's own path — pages/hello/[name].php —
 * is what makes it match GET /hello/{anything}, with {anything} handed in
 * as $params['name']. No entry in routes/web.php for this route at all.
 */
return function (array $params): string {
    $name = htmlspecialchars($params['name'] ?? 'stranger', ENT_QUOTES, 'UTF-8');

    return <<<HTML
        <!doctype html>
        <html lang="pt-br">
        <head><meta charset="utf-8"><title>Olá, {$name}</title></head>
        <body style="font-family: sans-serif; padding: 2rem;">
            <h1>Olá, {$name}!</h1>
            <p>Esta página foi descoberta por arquivo: <code>pages/hello/[name].php</code>,
            sem nenhuma linha em <code>routes/web.php</code>.</p>
        </body>
        </html>
        HTML;
};
