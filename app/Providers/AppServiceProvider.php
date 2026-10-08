<?php

namespace App\Providers;

use App\Database\Connectors\PostgresConnector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('db.connector.pgsql', fn () => new PostgresConnector);
    }
}
