<?php

namespace Nilisnone\LogViewer\Utils;

use Nilisnone\LogViewer\Exceptions\InvalidRegularExpression;

class Utils
{
    private static string $_cachedLocalIP;

    /**
     * Get a human-friendly readable string of the number of bytes provided.
     */
    public static function bytesForHumans(int $bytes): string
    {
        if ($bytes > ($gb = 1024 * 1024 * 1024)) {
            return number_format($bytes / $gb, 2).' GB';
        } elseif ($bytes > ($mb = 1024 * 1024)) {
            return number_format($bytes / $mb, 2).' MB';
        } elseif ($bytes > ($kb = 1024)) {
            return number_format($bytes / $kb, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Calculate the memory footprint of a given variable.
     * CAUTION: This will increase the memory usage by that same amount because it makes a copy of this variable.
     */
    public static function sizeOfVar(mixed $var): int
    {
        $start_memory = memory_get_usage();
        $tmp = unserialize(serialize($var));

        return memory_get_usage() - $start_memory;
    }

    /**
     * Calculate the memory footprint of a given variable and return it as a human-friendly string.
     * CAUTION: This will increase the memory usage by that same amount because it makes a copy of this variable.
     */
    public static function sizeOfVarInMB(mixed $var): string
    {
        return self::bytesForHumans(self::sizeOfVar($var));
    }

    public static function validateRegex(string $regexString, bool $throw = true): bool
    {
        $error = null;
        set_error_handler(function (int $errno, string $errstr) use (&$error) {
            $error = $errstr;
        }, E_WARNING);
        preg_match($regexString, '');
        restore_error_handler();

        if (! empty($error)) {
            $error = str_replace('preg_match(): ', '', $error);

            if ($throw) {
                throw new InvalidRegularExpression($error);
            }

            return false;
        }

        return true;
    }

    public static function shortMd5(string $content, int $length = 8): string
    {
        if ($length > 32) {
            $length = 32;
        }

        return substr(md5($content), -$length, $length);
    }

    public static function glob_recursive($pattern, $flags = 0): array
    {
        $files = glob($pattern, $flags) ?: [];
        $folders = glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [];

        foreach ($folders as $dir) {
            $files = array_merge($files, static::glob_recursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }

    public static function getLocalIP(bool $cached = true): string
    {
        if (isset(self::$_cachedLocalIP) && $cached) {
            return self::$_cachedLocalIP;
        }

        if (isset($_SERVER['SERVER_ADDR'])) {
            self::$_cachedLocalIP = $_SERVER['SERVER_ADDR'];
        } else {
            $os = php_uname('s');

            if (stripos($os, 'Linux') !== false) {
                $localIP = shell_exec("hostname -I | awk '{print $1}'"); // Linux systems
            } elseif (stripos($os, 'Darwin') !== false) {
                $localIP = shell_exec("ifconfig | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | head -n 1"); // macOS
            } else {
                $localIP = gethostbyname(gethostname()); // Fallback method
            }

            self::$_cachedLocalIP = trim($localIP ?? '');
        }

        return self::$_cachedLocalIP;
    }

    /**
     * Used for testing only. Do not use in your code.
     */
    public static function setCachedLocalIP(string $ip): void
    {
        self::$_cachedLocalIP = $ip;
    }
}
