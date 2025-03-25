<?php

namespace Nilisnone\LogViewer\Logs;

use Nilisnone\LogViewer\LogLevels\LaravelLogLevel;

class ZzLog extends Log
{
    public static string $name = 'zz_log';
    public static string $regex = '';
    public static string $levelClass = LaravelLogLevel::class;
    public static array $columns = [
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'LogChannel', 'data_path' => 'context.logChannel'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    public function __construct(string $text, ?string $fileIdentifier = null, ?int $filePosition = null, ?int $index = null)
    {
        $this->text = $text;
        $text = @json_decode($text, true);
        $this->fileIdentifier = $fileIdentifier;
        $this->filePosition = $filePosition;
        $this->index = $index;

        $this->datetime = static::parseDatetime($text['start'] ?? '');
        $this->level = strtoupper($text['level'] ?? 'info');
        $this->message = $text['msg'] ?? 'cannot found msg';
        $this->extra = @json_decode($text['extra'] ?? '', true) ?? [];
        $this->context = $text;
    }

    public static function matches(string $text, ?int &$timestamp = null, ?string &$level = null): bool
    {
        $text = @json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        if (empty($text['@timestamp']) && empty($text['env'])) {
            return false;
        }
        $timestamp = static::parseDatetime($text['start'] ?? $text['@timestamp'] ?? '')?->timestamp;
        $level = strtoupper($text['level'] ?? 'info');

        return true;
    }
}
