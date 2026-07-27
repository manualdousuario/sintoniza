<?php

use App\Http\Middleware\GpodderAuthenticate;
use App\Http\Middleware\SetLocale;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // gPodder / Nextcloud sync API (stateless, session-based, no CSRF)
            Route::middleware('gpodder')->group(base_path('routes/gpodder.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: (string) env('TRUSTED_PROXIES', '172.16.0.0/12,10.0.0.0/8,192.168.0.0/16,127.0.0.1'));

        // Same stack as "web" minus CSRF: gPodder clients authenticate via
        // HTTP Basic, URL tokens or the sessionid cookie.
        $middleware->group('gpodder', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            SetLocale::class,
            SubstituteBindings::class,
        ]);

        $middleware->appendToGroup('web', SetLocale::class);

        $middleware->alias([
            'gpodder.auth' => GpodderAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
