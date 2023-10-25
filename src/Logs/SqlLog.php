<?php

namespace Nilisnone\LogViewer\Logs;

use Nilisnone\LogViewer\LogLevels\LaravelLogLevel;
use function Symfony\Component\Translation\t;

class SqlLog extends Log
{
    public static string $name = 'Sql';
    public static string $regex = '';
    public static string $levelClass = LaravelLogLevel::class;
    public static array $columns = [
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'PID', 'data_path' => 'context.pid'],
        ['label' => 'Role', 'data_path' => 'context.role'],
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    public function __construct(string $text, string $fileIdentifier = null, int $filePosition = null, int $index = null)
    {
        $this->text = $text;
        $text = @json_decode($text, true);
        $this->fileIdentifier = $fileIdentifier;
        $this->filePosition = $filePosition;
        $this->index = $index;

        $this->datetime = static::parseDatetime($text['biz_created_at'] ?? $text['_timestamp'] ?? '');
        // todo level info
        $this->level = 'INFO';
        $this->message = $text['trace_sql'] ?? ($text['msg'] ?? '');
        $this->context = $text;
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        $text = @json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        if (!in_array($text['msg'] ?? '', ['trace-app', 'trace-sql'])) {
            return false;
        }
        $timestamp = static::parseDatetime($text['biz_created_at'] ?? $text['_timestamp'] ?? '')?->timestamp;
        $level = 'INFO';
        return true;
    }
}
