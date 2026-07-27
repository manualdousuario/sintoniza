<?php

use App\Http\Controllers\Gpodder\AuthController;
use App\Http\Controllers\Gpodder\DevicesController;
use App\Http\Controllers\Gpodder\EpisodesController;
use App\Http\Controllers\Gpodder\NextcloudController;
use App\Http\Controllers\Gpodder\StubController;
use App\Http\Controllers\Gpodder\SubscriptionsController;
use Illuminate\Support\Facades\Route;

/*
| gPodder / Nextcloud sync API. Registered with the "gpodder" middleware group
| (session, no CSRF). These URLs are a published client-facing contract —
| podcast apps hardcode them, so paths and formats must not change.
*/

// ------------------------------------------------------- Nextcloud compat
Route::post('/index.php/login/v2', [NextcloudController::class, 'login']);
Route::post('/index.php/login/v2/poll', [NextcloudController::class, 'poll']);
Route::match(['GET', 'POST'], '/index.php/apps/gpoddersync/{path}', [NextcloudController::class, 'gpoddersync'])
    ->where('path', '.*');

// --------------------------------------------------------------- Auth
Route::post('/api/2/auth/{username}/{action}.json', [AuthController::class, 'handle'])
    ->where(['username' => '[a-zA-Z0-9_-]+', 'action' => '[a-z]+']);

Route::middleware('gpodder.auth')->group(function () {

    // ------------------------------------------------------------ Devices
    Route::get('/api/2/devices/{username}.json', [DevicesController::class, 'index'])
        ->where('username', '[a-zA-Z0-9_-]+(__\w{10})?');
    Route::post('/api/2/devices/{username}/{deviceid}.json', [DevicesController::class, 'update'])
        ->where(['username' => '[a-zA-Z0-9_-]+(__\w{10})?', 'deviceid' => '[\w.-]+']);

    // ------------------------------------------------------ Subscriptions
    Route::get('/api/2/subscriptions/{username}/{deviceid}.json', [SubscriptionsController::class, 'delta'])
        ->where(['username' => '[a-zA-Z0-9_-]+(__\w{10})?', 'deviceid' => '[\w.-]+']);
    Route::put('/api/2/subscriptions/{username}/{deviceid}.txt', [SubscriptionsController::class, 'bulkAdd'])
        ->where(['username' => '[a-zA-Z0-9_-]+(__\w{10})?', 'deviceid' => '[\w.-]+']);
    Route::post('/api/2/subscriptions/{username}/{deviceid}.json', [SubscriptionsController::class, 'sync'])
        ->where(['username' => '[a-zA-Z0-9_-]+(__\w{10})?', 'deviceid' => '[\w.-]+']);

    // v1 subscription list
    Route::get('/subscriptions/{username}.{format}', [SubscriptionsController::class, 'v1List'])
        ->where(['username' => '[a-zA-Z0-9_-]+(__\w{10})?', 'format' => 'json|opml|txt']);

    // ---------------------------------------------------------- Episodes
    Route::get('/api/2/episodes/{username}.json', [EpisodesController::class, 'index'])
        ->where('username', '[a-zA-Z0-9_-]+(__\w{10})?');
    Route::post('/api/2/episodes/{username}.json', [EpisodesController::class, 'store'])
        ->where('username', '[a-zA-Z0-9_-]+(__\w{10})?');

    // --------------------------------------------------- Empty v1 stubs
    Route::get('/suggestions/{rest?}.{format}', [StubController::class, 'empty'])
        ->where(['rest' => '[^./]*', 'format' => 'json|txt']);
    Route::get('/toplist/{rest?}.{format}', [StubController::class, 'empty'])
        ->where(['rest' => '[^./]*', 'format' => 'json|txt']);

    // ----------------------------------------------------- Empty v2 stubs
    // (single route: same-URI routes would overwrite each other)
    Route::any('/api/2/{section}/{rest?}.json', [StubController::class, 'emptyOrUnavailable'])
        ->where(['section' => 'tags?|data|toplist|suggestions|favorites|settings|lists|sync-devices?', 'rest' => '.*']);

    // ---------------------------------------------------- Not implemented
    Route::any('/api/2/updates/{rest?}.json', [StubController::class, 'notImplemented'])
        ->where('rest', '.*');
});

// Unsupported output formats (jsonp / xml) => 501
Route::any('/api/2/{path}', [StubController::class, 'formatNotImplemented'])
    ->where('path', '.*\.(jsonp|xml)$');
