<?php

// PHP's built-in web server ("cli-server") sets SCRIPT_FILENAME to the requested file (e.g. "/demo/app.js")
// even though it runs this file as the router script. Symfony Runtime then tries to `require` that static
// file and crashes. Returning `false` here tells the dev server to serve the static file directly.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $file = __DIR__.$path;
    if (is_file($file)) {
        return false;
    }
}

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
