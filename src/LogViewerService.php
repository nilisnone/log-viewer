<?php

namespace Nilisnone\LogViewer;

use Composer\InstalledVersions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Nilisnone\LogViewer\Readers\IndexedLogReader;
use Nilisnone\LogViewer\Readers\LogReaderInterface;
use Nilisnone\LogViewer\Utils\Utils;

class LogViewerService
{
    const DEFAULT_MAX_LOG_SIZE_TO_DISPLAY = 131_072;    // 128 KB

    public static string $logFileClass = LogFile::class;
    public static string $logReaderClass = IndexedLogReader::class;
    protected ?Collection $_cachedFiles = null;
    protected string $_cachedTimezone;
    protected mixed $authCallback;
    protected int $maxLogSizeToDisplay = self::DEFAULT_MAX_LOG_SIZE_TO_DISPLAY;
    protected mixed $hostsResolver;
    protected string $layout = 'log-viewer::index';

    public function timezone(): string
    {
        if (! isset($this->_cachedTimezone)) {
            $this->_cachedTimezone = config('log-viewer.timezone')
                ?? config('app.timezone')
                ?? 'UTC';
        }

        return $this->_cachedTimezone;
    }

    protected function getLaravelLogFilePaths(): array
    {
        // Because we'll use the base path as a parameter for `glob`, we should escape any
        // glob's special characters and treat those as actual characters of the path.
        // We can assume this, because it's the actual path of the Laravel app, not a user-defined
        // search pattern.
        if (PHP_OS_FAMILY === 'Windows') {
            $baseDir = str_replace(
                ['[', ']'],
                ['{LEFTBRACKET}', '{RIGHTBRACKET}'],
                str_replace('\\', '/', $this->basePathForLogs())
            );
            $baseDir = str_replace(
                ['{LEFTBRACKET}', '{RIGHTBRACKET}'],
                ['[[]', '[]]'],
                $baseDir
            );
        } else {
            $baseDir = str_replace(
                ['*', '?', '\\', '[', ']'],
                ['\*', '\?', '\\\\', '\[', '\]'],
                $this->basePathForLogs()
            );
        }
        $files = [];
        $filePathsCollected = [];

        foreach (config('log-viewer.include_files', []) as $pattern => $alias) {
            if (is_numeric($pattern)) {
                $pattern = $alias;
                $alias = null;
            }

            if (! str_starts_with($pattern, DIRECTORY_SEPARATOR)) {
                $pattern = $baseDir.$pattern;
            }

            $filesMatchingPattern = $this->getFilePathsMatchingPattern($pattern);
            $filesMatchingPattern = array_map('realpath', $filesMatchingPattern);
            $filesMatchingPattern = array_values(array_filter($filesMatchingPattern, 'is_file'));
            $filesMatchingPattern = array_values(array_diff($filesMatchingPattern, $filePathsCollected));
            $filePathsCollected = array_merge($filePathsCollected, $filesMatchingPattern);

            // Let's prep aliases if they are provided.
            if (! empty($alias)) {
                $filesMatchingPattern = array_map(fn ($path) => [$path, $alias], $filesMatchingPattern);
            }

            $files = array_merge($files, $filesMatchingPattern);
        }

        foreach (config('log-viewer.exclude_files', []) as $pattern => $alias) {
            if (is_numeric($pattern)) {
                $pattern = $alias;
                $alias = null;
            }

            if (! str_starts_with($pattern, DIRECTORY_SEPARATOR)) {
                $pattern = $baseDir.$pattern;
            }

            $filesMatchingPattern = $this->getFilePathsMatchingPattern($pattern);
            $filesMatchingPattern = array_map('realpath', $filesMatchingPattern);
            $filesMatchingPattern = array_values(array_filter($filesMatchingPattern, 'is_file'));

            if (! empty($alias)) {
                $filesMatchingPattern = array_map(fn ($path) => [$path, $alias], $filesMatchingPattern);
            }

            $files = array_filter($files, function (string|array $file) use ($filesMatchingPattern) {
                if (is_array($file)) {
                    return ! in_array($file[0], $filesMatchingPattern);
                }

                return ! in_array($file, $filesMatchingPattern);
            });
        }

        return array_values(array_reverse($files));
    }

    protected function getFilePathsMatchingPattern($pattern): array
    {
        // The GLOB_BRACE flag is not available on some non GNU systems, like Solaris or Alpine Linux.

        if (str_contains($pattern, '**')) {
            return Utils::glob_recursive($pattern);
        }

        return glob($pattern) ?: [];
    }

    public function basePathForLogs(): string
    {
        return Str::finish(realpath(storage_path('logs')), DIRECTORY_SEPARATOR);
    }

    /**
     * @return LogFileCollection|LogFile[]
     */
    public function getFiles(): LogFileCollection
    {
        if (! isset($this->_cachedFiles)) {
            $fileClass = static::$logFileClass;

            $this->_cachedFiles = (new LogFileCollection($this->getLaravelLogFilePaths()))
                ->unique()
                ->map(function ($filePath) use ($fileClass) {
                    if (is_array($filePath)) {
                        [$filePath, $alias] = $filePath;
                    }

                    return new $fileClass($filePath, null, $alias ?? null);
                })
                ->values();

            if (config('log-viewer.hide_unknown_files', true)) {
                $this->_cachedFiles = $this->_cachedFiles->filter(function (LogFile $file) {
                    return ! $file->type()->isUnknown();
                });
            }
        }

        return $this->_cachedFiles;
    }

    public function getFilesGroupedByFolder(): LogFolderCollection
    {
        return LogFolderCollection::fromFiles($this->getFiles());
    }

    /**
     * Find the file with the given identifier or file name.
     */
    public function getFile(?string $fileIdentifier): ?LogFile
    {
        if (empty($fileIdentifier)) {
            return null;
        }

        $file = $this->getFiles()
            ->where('identifier', $fileIdentifier)
            ->first();

        if (! $file) {
            $file = $this->getFiles()
                ->where('name', $fileIdentifier)
                ->first();
        }

        return $file;
    }

    public function getFolder(?string $folderIdentifier): ?LogFolder
    {
        return $this->getFilesGroupedByFolder()
            ->first(function (LogFolder $folder) use ($folderIdentifier) {
                return (empty($folderIdentifier) && $folder->isRoot())
                    || $folder->identifier === $folderIdentifier
                    || $folder->path === $folderIdentifier;
            });
    }

    public function supportsHostsFeature(): bool
    {
        return class_exists(\GuzzleHttp\Client::class);
    }

    public function resolveHostsUsing(callable $callback): void
    {
        $this->hostsResolver = $callback;
    }

    public function getHosts(): HostCollection
    {
        $hosts = HostCollection::fromConfig(config('log-viewer.hosts', []));

        if (isset($this->hostsResolver)) {
            $hosts = new HostCollection(
                call_user_func($this->hostsResolver, $hosts) ?? []
            );

            $hosts->transform(function ($host, $key) {
                return is_array($host)
                    ? Host::fromConfig($key, $host)
                    : $host;
            });
        }

        return $hosts->values();
    }

    public function getHost(?string $hostIdentifier): ?Host
    {
        return $this->getHosts()
            ->first(fn (Host $host) => $host->identifier === $hostIdentifier);
    }

    public function clearFileCache(): void
    {
        $this->_cachedFiles = null;
    }

    public function getRouteDomain(): ?string
    {
        return config('log-viewer.route_domain');
    }

    public function getRoutePrefix(): string
    {
        return config('log-viewer.route_path', 'log-viewer');
    }

    public function getRouteMiddleware(): array
    {
        return config('log-viewer.middleware', []) ?: ['web'];
    }

    public function auth($callback = null): void
    {
        if (is_null($callback) && isset($this->authCallback)) {
            $canViewLogViewer = call_user_func($this->authCallback, request());

            if (! $canViewLogViewer) {
                throw new AuthorizationException('Unauthorized.');
            }
        } elseif (is_null($callback) && Gate::has('viewLogViewer')) {
            Gate::authorize('viewLogViewer');
        } elseif (! is_null($callback) && is_callable($callback)) {
            $this->authCallback = $callback;
        }
    }

    public function hasAuthCallback(): bool
    {
        return isset($this->authCallback);
    }

    public function lazyScanChunkSize(): int
    {
        return intval(config('log-viewer.lazy_scan_chunk_size_in_mb', 100)) * 1024 * 1024;
    }

    public function lazyScanTimeout(): float
    {
        return 5.0;    // 5 seconds
    }

    /**
     * Get the maximum number of bytes of the log that we should display.
     */
    public function maxLogSize(): int
    {
        return $this->maxLogSizeToDisplay;
    }

    public function setMaxLogSize(int $bytes): void
    {
        $this->maxLogSizeToDisplay = $bytes > 0 ? $bytes : self::DEFAULT_MAX_LOG_SIZE_TO_DISPLAY;
    }

    public function extend(string $type, string $class): void
    {
        app(LogTypeRegistrar::class)->register($type, $class);
    }

    public function useLogFileClass(string $class): void
    {
        // figure out whether the class extends from the LogFile class
        if (! is_subclass_of($class, LogFile::class)) {
            throw new \InvalidArgumentException("The class {$class} must extend from the ".LogFile::class.' class.');
        }

        static::$logFileClass = $class;
    }

    public function useLogReaderClass(string $class): void
    {
        // figure out whether the class implements the LogReaderInterface
        $reflection = new \ReflectionClass($class);

        if (! $reflection->implementsInterface(LogReaderInterface::class)) {
            throw new \InvalidArgumentException("The class {$class} must implement the LogReaderInterface.");
        }

        static::$logReaderClass = $class;
    }

    public function logReaderClass(): string
    {
        return static::$logReaderClass;
    }

    public function setViewLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function getViewLayout(): string
    {
        return $this->layout;
    }

    /**
     * Determine if Log Viewer's published assets are up-to-date.
     *
     * @throws \RuntimeException
     */
    public function assetsAreCurrent(): bool
    {
        $publishedPath = public_path('vendor/log-viewer/mix-manifest.json');

        if (! File::exists($publishedPath)) {
            throw new \RuntimeException('Log Viewer assets are not published. Please run: php artisan vendor:publish --tag=log-viewer-assets --force');
        }

        return File::get($publishedPath) === File::get(__DIR__.'/../public/mix-manifest.json');
    }

    /**
     * Get the current version of the Log Viewer
     */
    public function version(): string
    {
        if (app()->runningUnitTests()) {
            return 'unit-tests';
        }

        if (class_exists(InstalledVersions::class)) {
            return InstalledVersions::getPrettyVersion('nilisnone/log-viewer') ?? 'dev-main';
        } else {
            $composerJson = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);

            return is_array($composerJson) && isset($composerJson['version'])
                ? $composerJson['version']
                : 'dev-main';
        }
    }
}
