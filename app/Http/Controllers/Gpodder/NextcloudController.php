<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gpodder;

use App\Services\GpodderAuthService;
use App\Support\Gpodder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Nextcloud gPodder Sync compatibility layer (AntennaPod & co). */
class NextcloudController
{
    public function __construct(
        private GpodderAuthService $auth
    ) {}

    public function login(Request $request): JsonResponse
    {
        $token = bin2hex(random_bytes(16));

        return response()->json([
            'poll' => [
                'token' => $token,
                'endpoint' => Gpodder::url('index.php/login/v2/poll'),
            ],
            'login' => Gpodder::url('login?token='.$token),
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Poll for the login result. The token IS the session id of the
     * session created when the user logged in via /login?token=...
     */
    public function poll(Request $request): JsonResponse
    {
        $token = (string) $request->input('token', '');

        if ($token === '' || ! ctype_alnum($token)) {
            return Gpodder::error(400, __('sintoniza.messages.invalid_gpodder_token'));
        }

        $session = $request->session();
        $session->setId($token);
        $session->start();

        $user = Auth::user();
        $appPassword = $session->get('app_password');

        if (! $user || ! $appPassword) {
            return Gpodder::error(404, __('sintoniza.messages.session_expired'));
        }

        return response()->json([
            'server' => Gpodder::url(),
            'loginName' => $user->name,
            'appPassword' => $appPassword,
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * gpoddersync endpoints, rewritten to the v2 API behaviour:
     *   subscriptions | subscription_change/create => subscriptions "current"/"default"
     *   episode_action | episode_action/create     => episodes "current"
     */
    public function gpoddersync(Request $request, string $path): JsonResponse
    {
        $user = $this->auth->resolveFromAppPassword($request->getUser(), $request->getPassword());

        if (! $user) {
            return Gpodder::error(401, $this->auth->failureMessage);
        }

        Auth::setUser($user);

        if ($path === 'subscriptions' || $path === 'subscription_change/create') {
            $controller = app(SubscriptionsController::class);

            return $request->isMethod('GET')
                ? $controller->delta($request, 'current', 'default')
                : $controller->sync($request, 'current', 'default');
        }

        if ($path === 'episode_action' || $path === 'episode_action/create') {
            $controller = app(EpisodesController::class);

            return $request->isMethod('GET')
                ? $controller->index($request, 'current')
                : $controller->store($request, 'current');
        }

        return Gpodder::error(404, __('sintoniza.messages.nextcloud_undefined_endpoint'));
    }
}
