<?php

use Nilisnone\LogViewer\LogFile;
use Nilisnone\LogViewer\LogFolder;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogFolder;
use Opcodes\LogViewer\Utils\Utils;

test('LogFolder can get the earliest timestamp of the files it contains', function () {
    $firstFile = Mockery::mock(new LogFile('folder/test.log'))
        ->allows(['earliestTimestamp' => now()->subDay()->timestamp]);
    $secondFile = Mockery::mock(new LogFile('folder/test2.log'))
        ->allows(['earliestTimestamp' => now()->subDays(2)->timestamp]);
    $folder = new LogFolder('folder', [$firstFile, $secondFile]);

    expect($folder->earliestTimestamp())->toBe($secondFile->earliestTimestamp());
});

test('LogFolder can get the latest timestamp of the files it contains', function () {
    $firstFile = Mockery::mock(new LogFile('folder/test.log'))
        ->allows(['latestTimestamp' => now()->subDay()->timestamp]);
    $secondFile = Mockery::mock(new LogFile('folder/test2.log'))
        ->allows(['latestTimestamp' => now()->subDays(2)->timestamp]);
    $folder = new LogFolder('folder', [$firstFile, $secondFile]);

    expect($folder->latestTimestamp())->toBe($firstFile->latestTimestamp());
});

test('log folder identifier is based on server address', function () {
    // Set the cached local IP to a known value:
    Utils::setCachedLocalIP($serverIp = '123.123.123.123');

    $folder = new LogFolder('folder', []);

    expect($folder->identifier)->toBe(
        Utils::shortMd5($serverIp.':'.$folder->path)
    );
});
