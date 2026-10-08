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
    putenv('MONITORING_HEARTBEAT_FILE='); // cron artisan tidak ada di serverless — health check scheduler dilewati
}

require $root.'/vendor/autoload.php';

$app = require_once $root.'/bootstrap/app.php';
$app->useStoragePath($tmp);

if (($_SERVER['REQUEST_URI'] ?? '') === '/__diag__') {
    header('Content-Type: text/plain; charset=utf-8');
    try {
        $app->boot();
        echo "trans: "; var_dump(@trans('Server Error', [], null));
    } catch (Throwable $e) {
        echo 'trans ERROR: '.get_class($e).': '.$e->getMessage()."\n@".$e->getFile().':'.$e->getLine()."\n";
    }
    try {
        $kernel = $app->make(Kernel::class);
        $sub = Request::create('/', 'GET');
        $resp = $kernel->handle($sub);
        echo "GET / status: ".$resp->getStatusCode()."\n";
    } catch (Throwable $e) {
        echo 'DISPATCH ERROR: '.get_class($e).': '.$e->getMessage()."\n@".$e->getFile().':'.$e->getLine()."\n";
    }
    exit;
}

$kernel = $app->make(Kernel::class);

try {
    $response = $kernel->handle($request = Request::capture());
} catch (Throwable $e) {
    file_put_contents("{$tmp}/logs/fatal.log", get_class($e).': '.$e->getMessage()."\n".$e->getTraceAsString());
    fwrite(STDERR, 'FATAL-OUTSIDE-HANDLER: '.get_class($e).': '.$e->getMessage()."\n");
    throw $e;
}
$response->send();
$kernel->terminate($request, $response);
