<?php

declare(strict_types=1);

/**
 * Legacy sites don't get a build step, so this tiny script hands the browser
 * the exact same client.js shipped by the push-hub package, with the right
 * MIME type, instead of forcing the site to add an asset pipeline.
 */

header('Content-Type: application/javascript; charset=utf-8');
readfile(__DIR__ . '/../../packages/core/push-hub/resources/client.js');
