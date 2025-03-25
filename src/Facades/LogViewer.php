<?php

namespace Nilisnone\LogViewer\Facades;

use Illuminate\Support\Facades\Facade;
use Nilisnone\LogViewer\Host;
use Nilisnone\LogViewer\HostCollection;
use Nilisnone\LogViewer\LogFile;
use Nilisnone\LogViewer\LogFileCollection;
use Nilisnone\LogViewer\LogFolder;
use Nilisnone\LogViewer\LogFolderCollection;
use Nilisnone\LogViewer\Readers\LogReaderInterface;

/**
 * @see \Nilisnone\LogViewer\LogViewerService
 *
 * @method static string version()
 * @method static string timezone()
 * @method static bool assetsAreCurrent()
 * @method static bool supportsHostsFeature()
 * @method static void resolveHostsUsing(callable $callback)
 * @method static Host[]|HostCollection getHosts()
 * @method static Host|null getHost(?string $hostIdentifier)
 * @method static LogFolder[]|LogFolderCollection getFilesGroupedByFolder()
 * @method static LogFolder|null getFolder(?string $folderIdentifier)
 * @method static LogFile[]|LogFileCollection getFiles()
 * @method static LogFile|null getFile(string $fileIdentifier)
 * @method static void clearFileCache()
 * @method static string|null getRouteDomain()
 * @method static array getRouteMiddleware()
 * @method static string getRoutePrefix()
 * @method static void auth($callback = null)
 * @method static void setMaxLogSize(int $bytes)
 * @method static int maxLogSize()
 * @method static int lazyScanChunkSize()
 * @method static float lazyScanTimeout()
 * @method static string basePathForLogs()
 * @method static void extend(string $type, string $class)
 * @method static void useLogFileClass(string $class)
 * @method static void useLogReaderClass(string $class)
 * @method static string|LogReaderInterface logReaderClass()
 */
class LogViewer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log-viewer';
    }
}
