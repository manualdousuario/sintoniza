<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gpodder;

use App\Services\GpodderAuthService;
use App\Support\Gpodder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * POST /api/2/auth/{username}/{action}.json  (login / logout)
 */
class AuthController
{
    public function __construct(
        private GpodderAuthService $auth
    ) {}

    public function handle(Request $request, string $username, string $action): JsonResponse
    {
        if ($action === 'logout') {
            Auth::logout();
            $request->session()->invalidate();

            return Gpodder::error(200, __('sintoniza.messages.logged_out'));
        }

        if ($action !== 'login') {
            return Gpodder::error(404, __('sintoniza.messages.unknown_login_action').' '.$action);
        }

        $user = $this->auth->resolveFromBasicAuth($request->getUser(), $request->getPassword());

        if (! $user) {
            return Gpodder::error(401, $this->auth->failureMessage);
        }

        Auth::login($user);

        return Gpodder::error(200, __('sintoniza.messages.login_success'));
    }
}
