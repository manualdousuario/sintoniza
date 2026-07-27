<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LanguageController
{
    public function __construct(
        private UserService $users
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $language = (string) $request->input('language', '');
        $normalized = str_replace('-', '_', $language);

        if (in_array($normalized, (array) config('sintoniza.locales', ['en']), true)) {
            $user = Auth::user();

            if ($user) {
                try {
                    $this->users->updateLanguage($user, $normalized);
                } catch (ValidationException) {
                    // ignore, keep previous language
                }
            } else {
                $request->session()->put('language', $normalized);
            }
        }

        return back();
    }
}
