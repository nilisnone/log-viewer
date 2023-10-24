<?php

namespace Nilisnone\LogViewer\Utils;

use Nilisnone\LogViewer\Facades\LogViewer;
use Nilisnone\LogViewer\LogFile;
use Nilisnone\LogViewer\LogIndex;

class GenerateCacheKey
{
    public static function for(mixed $object, string $namespace = null): string
    {
        $key = '';

        if ($object instanceof LogFile) {
            $key = self::baseKey().':file:'.$object->identifier;
        }

        if ($object instanceof LogIndex) {
            $key = self::for($object->file).':'.$object->identifier;
        }

        if (is_string($object)) {
            $key = self::baseKey().':'.$object;
        }

        if (! empty($namespace)) {
            $key .= ':'.$namespace;
        }

        return $key;
    }

    protected static function baseKey(): string
    {
        return 'lv:'.LogViewer::version();
    }
}
