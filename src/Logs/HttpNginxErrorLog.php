<?php

namespace Nilisnone\LogViewer\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Nilisnone\LogViewer\LogLevels\NginxStatusLevel;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogLevels\NginxStatusLevel;

class HttpNginxErrorLog extends Log
{
    public static string $name = 'HTTP Errors (Nginx)';
    public static string $regex = '~^(?P<datetime>[\d+\/ :]+) \[(?P<level>.+?)\] .*?: (?P<errormessage>(?:(?!, client: |, server: |, request: |, upstream: |, host: |, referrer: ).)*(?:\n(?![\d/]|\Z).*)*?)(?:, client: (?P<client>.+?))?(?:, server: (?P<server>.+?))?(?:, request: "?(?P<request>.+?)"?)?(?:, upstream: "?(?P<upstream>.+?)"?)?(?:, host: "?(?P<host>.+?)"?)?(?:, referrer: "?(?P<referrer>.+?)"?)?$~ms';
    public static string $levelClass = NginxStatusLevel::class;

    protected function fillMatches(array $matches = []): void
    {
        $datetime = static::parseDateTime($matches['datetime'] ?? null);
        $this->datetime = $datetime?->setTimezone(LogViewer::timezone());

        $this->level = $matches['level'] ?? null;
        $this->message = $matches['errormessage'] ?? null;

        $this->context = [
            'client' => $matches['client'] ?? null,
            'server' => $matches['server'] ?? null,
            'request' => $matches['request'] ?? null,
            'host' => $matches['host'] ?? null,
            'upstream' => $matches['upstream'] ?? null,
            'referrer' => $matches['referrer'] ?? null,
        ];
    }

    public static function parseDateTime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::createFromFormat('Y/m/d H:i:s', $datetime) : null;
    }
}
