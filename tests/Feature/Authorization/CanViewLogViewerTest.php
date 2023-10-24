<?php

use Illuminate\Support\Facades\Gate;
use Nilisnone\LogViewer\Facades\LogViewer;

use function Pest\Laravel\get;

test('can define an "auth" callback for authorization', function () {
    get(route('log-viewer.index'))->assertOk();

    // with the gate defined and a false value, it should not be possible to access the log viewer
    LogViewer::auth(fn ($request) => false);
    get(route('log-viewer.index'))->assertForbidden();

    // now let's give them access
    LogViewer::auth(fn ($request) => true);
    get(route('log-viewer.index'))->assertOk();
});

test('the "auth" callback is given with a Request object to check against', function () {
    LogViewer::auth(function ($request) {
        expect($request)->toBeInstanceOf(\Illuminate\Http\Request::class);

        return true;
    });

    get(route('log-viewer.index'))->assertOk();
});

test('can define a "viewLogViewer" gate as an alternative', function () {
    get(route('log-viewer.index'))->assertOk();

    Gate::define('viewLogViewer', fn ($user = null) => false);
    get(route('log-viewer.index'))->assertForbidden();

    Gate::define('viewLogViewer', fn ($user = null) => true);
    get(route('log-viewer.index'))->assertOk();
});
