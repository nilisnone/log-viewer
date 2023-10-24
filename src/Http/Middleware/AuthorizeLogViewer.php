<?php

namespace Nilisnone\LogViewer\Http\Middleware;

use Nilisnone\LogViewer\Facades\LogViewer;

class AuthorizeLogViewer
{
    public function handle($request, $next)
    {
        LogViewer::auth();

        return $next($request);
    }
}
