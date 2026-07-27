<?php

namespace App\Http\Middleware;

use App\Services\GpodderAuthService;
use App\Support\Gpodder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the gPodder API user, in order: "username__token" in the URL, then
 * HTTP Basic auth, then the sessionid cookie shared with the web session.
 */
class GpodderAuthenticate
{
    public function __construct(
        private GpodderAuthService $auth
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) $request->route('username', '');

        if ($username !== '' && str_contains($username, '__')) {
            $user = $this->auth->resolveFromToken($username);

            if (! $user) {
                return Gpodder::error(401, $this->auth->failureMessage);
            }

            Auth::setUser($user);

            return $next($request);
        }

        if ($request->getUser() && $request->getPassword()) {
            $user = $this->auth->resolveFromBasicAuth($request->getUser(), $request->getPassword());

            if (! $user) {
                return Gpodder::error(401, $this->auth->failureMessage);
            }

            Auth::setUser($user);

            return $next($request);
        }

        if (! $request->cookies->get((string) config('session.cookie'))) {
            return Gpodder::error(401, __('sintoniza.messages.session_cookie_required'));
        }

        if (! Auth::check()) {
            return Gpodder::error(401, __('sintoniza.messages.session_expired'));
        }

        return $next($request);
    }
}
