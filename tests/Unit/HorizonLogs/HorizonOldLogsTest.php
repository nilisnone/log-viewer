<?php

use Nilisnone\LogViewer\LogFile;
use Nilisnone\LogViewer\Logs\LogType;

it('can process old Horizon logs', function () {
    $file = generateLogFile('horizon_old_dummy.log', content: <<<EOF
[2022-10-07 09:41:00][13acffa6-fd25-4d36-876a-b48e968302a4] Processing: App\Notifications\BookingUpdated
[2022-10-07 09:41:00][13acffa6-fd25-4d36-876a-b48e968302a4] Processed:  App\Notifications\BookingUpdated
EOF);
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::HORIZON_OLD); // HorizonOldLog

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(2)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2022-10-07 09:41:00')
        ->and($logs[0]->level)->toBe('Processing')
        ->and($logs[0]->message)->toBe('App\Notifications\BookingUpdated')
        ->and($logs[0]->context['uuid'])->toBe('13acffa6-fd25-4d36-876a-b48e968302a4')
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2022-10-07 09:41:00')
        ->and($logs[1]->level)->toBe('Processed')
        ->and($logs[1]->message)->toBe('App\Notifications\BookingUpdated')
        ->and($logs[1]->context['uuid'])->toBe('13acffa6-fd25-4d36-876a-b48e968302a4');
});
