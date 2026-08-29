<?php

declare(strict_types=1);

/**
 * Same reasoning as push-hub-client.js.php: legacy sites have no build step,
 * so this hands the browser the vendored Idiomorph script with the right
 * MIME type instead of requiring an asset pipeline.
 */

$sourceFile = __DIR__ . '/../../packages/core/push-hub/resources/vendor/idiomorph/idiomorph.min.js';

header('Content-Type: application/javascript; charset=utf-8');
header(isset($_GET['v'])
    ? 'Cache-Control: public, max-age=31536000, immutable'
    : 'Cache-Control: no-cache');
readfile($sourceFile);
