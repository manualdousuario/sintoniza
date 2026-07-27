<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController
{
    public function __construct(
        private UserService $users
    ) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.login', [
            'hasToken' => $request->query->has('token'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->users->authenticate($request->input('login'), $request->input('password'));

        if (! $user) {
            return back()->with('error', __('sintoniza.messages.invalid_username_password'))
                ->onlyInput('login');
        }

        if (! $user->is_active) {
            return back()->with('error', __('sintoniza.messages.account_disabled'))
                ->onlyInput('login');
        }

        $token = $request->query('token');

        if ($token && ctype_alnum($token)) {
            // Nextcloud login flow v2: bind the session to the poll token so
            // /index.php/login/v2/poll can retrieve the app password.
            $session = $request->session();
            $session->setId($token);
            $session->start();

            Auth::login($user);
            $session->put('app_password', "{$token}:".sha1($user->password.$token));

            return redirect('/dashboard?oktoken');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
