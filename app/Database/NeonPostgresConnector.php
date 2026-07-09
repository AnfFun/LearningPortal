<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\Str;

class NeonPostgresConnector extends PostgresConnector
{
    /**
     * Create a DSN string from a configuration.
     *
     * @param  array<string, mixed>  $config
     */
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);
        $endpoint = $config['endpoint'] ?? null;
        $host = $config['host'] ?? null;

        if ((! is_string($endpoint) || $endpoint === '')
            && is_string($host)
            && Str::endsWith($host, '.neon.tech')) {
            $endpoint = Str::of($host)
                ->before('.')
                ->beforeLast('-pooler')
                ->toString();
        }

        if (is_string($endpoint) && $endpoint !== '') {
            $escapedEndpoint = Str::of($endpoint)
                ->replace('\\', '\\\\')
                ->replace("'", "\\'")
                ->toString();

            $dsn .= ";options='endpoint={$escapedEndpoint}'";
        }

        return $dsn;
    }
}
