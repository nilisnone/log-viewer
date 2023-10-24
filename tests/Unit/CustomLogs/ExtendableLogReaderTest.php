<?php

use Nilisnone\LogViewer\Facades\LogViewer;
use Nilisnone\LogViewer\Logs\LogType;
use Nilisnone\LogViewer\LogTypeRegistrar;
use Nilisnone\LogViewer\Tests\Unit\CustomLogs\CustomAccessLog;
use Nilisnone\LogViewer\Tests\Unit\CustomLogs\CustomHttpAccessLog;

beforeEach(function () {
    $this->logRegistrar = app(LogTypeRegistrar::class);
});

it('can extend with another log format', function () {
    LogViewer::extend('custom_log', CustomAccessLog::class);

    expect($this->logRegistrar->getClass('custom_log'))->toBe(CustomAccessLog::class);
});

it('cannot extend with a non-BaseLog class', function () {
    LogViewer::extend('custom_log', stdClass::class);
})->throws(InvalidArgumentException::class);

it('cannot extend with a non-existent class', function () {
    LogViewer::extend('custom_log', 'NonExistentClass');
})->throws(InvalidArgumentException::class);

it('overrides an existing class with the same type', function () {
    expect($this->logRegistrar->getClass(LogType::LARAVEL))->toBe(\Nilisnone\LogViewer\Logs\LaravelLog::class);

    LogViewer::extend('laravel', CustomAccessLog::class);

    expect($this->logRegistrar->getClass(LogType::LARAVEL))->toBe(CustomAccessLog::class);
});

it('can guess the type from the provided first line', function ($expectedType, $line) {
    expect($this->logRegistrar->guessTypeFromFirstLine($line))
        ->toBe($expectedType);
})->with([
    [
        'expectedType' => LogType::LARAVEL,
        'line' => '[2021-01-01 00:00:00] laravel.INFO: Test log message',
    ],
    [
        'expectedType' => LogType::HTTP_ACCESS,
        'line' => '8.68.121.11 - - [01/Feb/2023:01:53:51 +0000] "POST /main/tag/category HTTP/2.0" 404 4819 "-" "-"',
    ],
    [
        'expectedType' => LogType::HTTP_ERROR_APACHE,
        'line' => '[Sun Jul 09 06:21:31.657578 2023] [ssl:error] [pid 44651] AH02032: Hostname test.example.com provided via SNI and hostname system.test provided via HTTP are different',
    ],
    [
        'expectedType' => LogType::HTTP_ERROR_NGINX,
        'line' => '2023/01/04 11:18:33 [alert] 95160#0: *1473 setsockopt(TCP_NODELAY) failed (22: Invalid argument) while keepalive, client: 127.0.0.1, server: 127.0.0.1:80',
    ],
]);

it('handles unaccessible files', function () {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('File permissions work differently on Windows. The feature tested might still work.');
    }

    $file = generateLogFile(randomContent: true);
    chmod($file->path, 0333); // prevent reading

    expect($this->logRegistrar->guessTypeFromFirstLine($file))
        ->toBeNull();
});

it('prefers user-defined log types over default ones', function () {
    // first, the default http access log
    $defaultAccessLogLine = '8.68.121.11 - UID 123 - [01/Feb/2023:01:53:51 +0000] "POST /main/tag/category HTTP/2.0" 404 4819 "-" "-"';

    expect($this->logRegistrar->guessTypeFromFirstLine($defaultAccessLogLine))
        ->toBe(LogType::HTTP_ACCESS);

    // now, let's extend with a custom user-defined log type that can also process this same line
    LogViewer::extend('http_access_custom', CustomHttpAccessLog::class);

    expect($this->logRegistrar->guessTypeFromFirstLine($defaultAccessLogLine))
        ->toBe('http_access_custom');
});
