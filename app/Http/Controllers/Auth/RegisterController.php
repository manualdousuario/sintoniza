<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Services\CaptchaService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController
{
    public function __construct(
        private UserService $users,
        private CaptchaService $captcha
    ) {}

    public function showRegister(): View
    {
        if (! $this->users->canSubscribe()) {
            return view('auth.register', [
                'disabled' => true,
                'captcha' => null,
            ]);
        }

        return view('auth.register', [
            'disabled' => false,
            'captcha' => $this->captcha->generate(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        if (! $this->users->canSubscribe()) {
            return redirect('/register');
        }

        if (! $this->captcha->check($request->input('captcha', ''))) {
            return back()->with('error', __('sintoniza.messages.invalid_captcha'))
                ->withInput($request->only('username', 'email'));
        }

        try {
            $this->users->register(
                $request->input('username', ''),
                $request->input('password', ''),
                $request->input('email', '')
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->implode(' '))
                ->withInput($request->only('username', 'email'));
        }

        return redirect('/login')->with('success', __('sintoniza.messages.user_registered'));
    }
}
