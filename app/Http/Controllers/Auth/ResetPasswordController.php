<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ResetPasswordController
{
    public function show(Request $request): View
    {
        return view('auth.forget-password.reset', [
            'token' => $request->query('token', ''),
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'new_password' => ['required', 'string', 'min:8'],
        ], [
            'new_password.min' => __('sintoniza.messages.password_too_short'),
        ]);

        $status = Password::reset(
            [
                'email' => $request->input('email'),
                'token' => $request->input('token', $request->query('token', '')),
                'password' => $request->input('new_password'),
                'password_confirmation' => $request->input('new_password'),
            ],
            function ($user, $password) {
                $user->update(['password' => $password]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return back()->with('message', __('sintoniza.messages.password_reset_success'));
        }

        return back()->with('error', __('sintoniza.messages.invalid_reset_token'));
    }
}
