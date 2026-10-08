<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$root = dirname(__DIR__);
$tmp = '/tmp/kebun-tebu';

foreach (['app', 'framework/cache', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    if (! is_dir("{$tmp}/{$directory}")) {
        mkdir("{$tmp}/{$directory}", 0777, true);
    }
}

putenv("VIEW_COMPILED_PATH={$tmp}/framework/views");
putenv("APP_PACKAGES_CACHE={$tmp}/framework/cache/packages.php");
putenv("APP_SERVICES_CACHE={$tmp}/framework/cache/services.php");
putenv("APP_CONFIG_CACHE={$tmp}/framework/cache/config.php");
putenv("APP_ROUTES_CACHE={$tmp}/framework/cache/routes-v7.php");
if (getenv('MONITORING_HEARTBEAT_FILE') === false) {
    putenv('MONITORING_HEARTBEAT_FILE=');
}

require $root.'/vendor/autoload.php';

$app = require_once $root.'/bootstrap/app.php';
$app->useStoragePath($tmp);

$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request = Request::capture());
$response->send();
$kernel->terminate($request, $response);