<?php

namespace Nilisnone\LogViewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Illuminate\Contracts\Cache\Repository
 *
 * @see \Illuminate\Cache\Repository
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log-viewer-cache';
    }
}
