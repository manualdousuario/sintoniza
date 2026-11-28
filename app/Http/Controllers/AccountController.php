<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\UserService;
use App\Support\Gpodder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The only signed-in page: how to connect an app, the synced devices and
 * subscriptions, and the account settings.
 */
class AccountController
{
    public function __construct(
        private UserService $users
    ) {}

    public function show(Request $request): View
    {
        $user = Auth::user();

        return view('account', [
            'user' => $user,
            'serverUrl' => Gpodder::url(),
            'token' => $this->users->getUserToken($user),
            'devices' => $user->devices()->orderBy('name')->get(),
            'subscriptions' => $user->subscriptions()->orderBy('url')->get(['id', 'url']),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'okToken' => $request->query->has('oktoken'),
        ]);
    }

    public function unsubscribe(int $id): RedirectResponse
    {
        Subscription::where('user_id', Auth::id())->where('id', $id)->first()?->delete();

        return to_route('dashboard')
            ->withFragment('subscriptions')
            ->with('success', __('sintoniza.messages.unsubscribed'));
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            $this->users->updateLanguage($user, (string) $request->input('language', ''));
            $this->users->updateTimezone($user, (string) $request->input('timezone', ''));
        } catch (ValidationException $e) {
            return to_route('dashboard')->withFragment('settings')->withErrors($e->errors());
        }

        return to_route('dashboard')
            ->withFragment('settings')
            ->with('success', __('sintoniza.account.preferences_saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->users->changePassword(
                Auth::user(),
                (string) $request->input('current_password'),
                (string) $request->input('new_password'),
            );
        } catch (ValidationException $e) {
            return to_route('dashboard')->withFragment('password')->withErrors($e->errors());
        }

        return to_route('dashboard')
            ->withFragment('password')
            ->with('success', __('sintoniza.profile.password_changed'));
    }
}
