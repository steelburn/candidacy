<?php

namespace App\Infrastructure\Logging;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

/**
 * Custom JSON formatter for Laravel logs to be consumed by Promtail/Loki
 * 
 * Outputs logs in the format:
 * {"level":"info","message":"Log message","timestamp":"2024-01-01T00:00:00.000000Z","context":{...}}
 */
class JsonFormatter extends NormalizerFormatter
{
    /**
     * @param string $dateFormat The format of the timestamp: one supported by DateTime::format
     */
    public function __construct(string $dateFormat = 'Y-m-d\TH:i:s.u\Z')
    {
        parent::__construct($dateFormat);
    }

    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $output = [
            'level' => strtolower($record->level->getName()),
            'message' => $record->message,
            'timestamp' => $record->datetime->format($this->dateFormat),
            'service' => env('APP_NAME', 'laravel'),
        ];

        // Add context if present
        if (!empty($record->context)) {
            $output['context'] = $record->context;
        }

        // Add extra data if present
        if (!empty($record->extra)) {
            $output['extra'] = $record->extra;
        }

        return json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records): string
    {
        $output = '';
        foreach ($records as $record) {
            $output .= $this->format($record);
        }

        return $output;
    }
}
