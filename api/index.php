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

if (($_SERVER['REQUEST_URI'] ?? '') === '/__diag__') {
    header('Content-Type: text/plain; charset=utf-8');
    foreach (['laravel.log', 'fatal.log'] as $f) {
        $p = "{$tmp}/logs/{$f}";
        echo "=== {$f} ===\n";
        echo is_file($p) ? file_get_contents($p) : "(missing)\n";
        echo "\n";
    }
    exit;
}

require $root.'/vendor/autoload.php';

$app = require_once $root.'/bootstrap/app.php';
$app->useStoragePath($tmp);

$kernel = $app->make(Kernel::class);

if (($_SERVER['REQUEST_URI'] ?? '') === '/__diag__') {
    header('Content-Type: text/plain; charset=utf-8');
    foreach (['laravel.log', 'fatal.log'] as $f) {
        $p = "{$tmp}/logs/{$f}";
        echo "=== {$f} ===\n";
        echo is_file($p) ? file_get_contents($p) : "(missing)\n";
        echo "\n";
    }
    exit;
}

try {
    $response = $kernel->handle($request = Request::capture());
} catch (Throwable $e) {
    file_put_contents("{$tmp}/logs/fatal.log", get_class($e).': '.$e->getMessage()."\n".$e->getTraceAsString());
    fwrite(STDERR, 'FATAL-OUTSIDE-HANDLER: '.get_class($e).': '.$e->getMessage()."\n");
    throw $e;
}
$response->send();
$kernel->terminate($request, $response);
