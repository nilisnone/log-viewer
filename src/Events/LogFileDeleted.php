<?php

namespace Nilisnone\LogViewer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Nilisnone\LogViewer\LogFile;

class LogFileDeleted
{
    use Dispatchable;

    public function __construct(
        public LogFile $file
    ) {}
}
