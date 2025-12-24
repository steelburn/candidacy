<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider to enable database query logging
 */
class QueryLoggingServiceProvider extends ServiceProvider
{
    /**
     * Threshold for slow queries in milliseconds
     */
    private const SLOW_QUERY_THRESHOLD = 100;

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only enable query logging if configured
        if (config('database.log_queries', false)) {
            DB::listen(function ($query) {
                $this->logQuery($query);
            });
        }
    }

    /**
     * Log database query with execution time
     */
    private function logQuery($query): void
    {
        $sql = $query->sql;
        $bindings = $query->bindings;
        $time = $query->time;

        // Replace bindings in SQL for readability
        $formattedSql = $this->formatSqlWithBindings($sql, $bindings);

        $data = [
            'sql' => $formattedSql,
            'bindings' => $bindings,
            'time_ms' => $time,
            'connection' => $query->connectionName,
        ];

        // Log slow queries as warnings
        if ($time > self::SLOW_QUERY_THRESHOLD) {
            Log::warning('Slow Database Query', $data);
        } else {
            Log::debug('Database Query', $data);
        }
    }

    /**
     * Format SQL with bindings for better readability
     */
    private function formatSqlWithBindings(string $sql, array $bindings): string
    {
        if (empty($bindings)) {
            return $sql;
        }

        $formattedSql = $sql;
        
        foreach ($bindings as $binding) {
            $value = $this->formatBinding($binding);
            $formattedSql = preg_replace('/\?/', $value, $formattedSql, 1);
        }

        return $formattedSql;
    }

    /**
     * Format a binding value for SQL display
     */
    private function formatBinding($binding): string
    {
        if (is_null($binding)) {
            return 'NULL';
        }

        if (is_bool($binding)) {
            return $binding ? 'TRUE' : 'FALSE';
        }

        if (is_numeric($binding)) {
            return (string) $binding;
        }

        if ($binding instanceof \DateTime) {
            return "'" . $binding->format('Y-m-d H:i:s') . "'";
        }

        // Escape single quotes and wrap in quotes
        return "'" . str_replace("'", "''", $binding) . "'";
    }
}
