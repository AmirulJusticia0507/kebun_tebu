<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$root = dirname(__DIR__);
$tmp = '/tmp/kebun-tebu';

foreach (['framework/cache', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    if (! is_dir("{$tmp}/{$directory}")) {
        mkdir("{$tmp}/{$directory}", 0777, true);
    }
}

putenv("VIEW_COMPILED_PATH={$tmp}/framework/views");

require $root . '/vendor/autoload.php';

$app = require_once $root . '/bootstrap/app.php';
$app->useStoragePath($tmp);

$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request = Request::capture());
$response->send();
$kernel->terminate($request, $response);
