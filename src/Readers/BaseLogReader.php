<?php

namespace Nilisnone\LogViewer\Readers;

use Nilisnone\LogViewer\Concerns;
use Nilisnone\LogViewer\LogFile;
use Nilisnone\LogViewer\LogLevels\LevelInterface;
use Nilisnone\LogViewer\Logs\Log;

abstract class BaseLogReader
{
    use Concerns\LogReader\KeepsFileHandle;
    use Concerns\LogReader\KeepsInstances;

    protected LogFile $file;

    /** @var string|Log */
    protected string $logClass;

    /** @var string|LevelInterface */
    protected string $levelClass;

    public function __construct(LogFile $file)
    {
        $this->file = $file;
        $this->logClass = $this->file->type()->logClass() ?? Log::class;
        $this->levelClass = $this->logClass::levelClass();
    }

    protected function makeLog(string $text, int $filePosition, int $index): Log
    {
        return new $this->logClass($text, $this->file->identifier, $filePosition, $index);
    }

    public function __destruct()
    {
        $this->closeFile();
    }
}
