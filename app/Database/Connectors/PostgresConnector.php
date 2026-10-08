<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector as LaravelPostgresConnector;

class PostgresConnector extends LaravelPostgresConnector
{
    /**
     * Add Neon's endpoint startup option for clients without TLS SNI support.
     */
    protected function addServerOptions($dsn, array $config)
    {
        if (! empty($config['neon_endpoint_id'])) {
            $endpoint = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $config['neon_endpoint_id']);

            return $dsn.";options='endpoint={$endpoint}'";
        }

        return parent::addServerOptions($dsn, $config);
    }
}
