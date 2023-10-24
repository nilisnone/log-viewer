<?php

namespace Nilisnone\LogViewer\Http\Controllers;

use Nilisnone\LogViewer\Facades\LogViewer;
use Nilisnone\LogViewer\Http\Resources\LogViewerHostResource;

class HostsController
{
    public function index()
    {
        return LogViewerHostResource::collection(
            LogViewer::getHosts()
        );
    }
}
