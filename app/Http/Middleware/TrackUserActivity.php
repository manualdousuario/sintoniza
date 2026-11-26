<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records when an authenticated user was last seen (web or gPodder API),
 * which drives the inactive-account purge.
 */
class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();

        if ($user instanceof User) {
            $user->touchActivity();
        }

        return $response;
    }
}
