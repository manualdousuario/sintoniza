<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController
{
    public function show(): View
    {
        return view('auth.forget-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            // Answer identically either way, so the response cannot be used to
            // probe which email addresses have accounts.
            $token = Password::createToken($user);
            $user->sendPasswordResetNotification($token);
        }

        return back()->with('message', __('sintoniza.forget_password.email_sent'));
    }
}
