<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Profile extends Component
{
    public string $current_password = '';

    public string $new_password = '';

    public string $confirm_password = '';

    public string $timezone = 'UTC';

    public string $language = 'en';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->timezone = $user->timezone ?: 'UTC';
        $this->language = $user->language ?: 'en';
    }

    public function changePassword(UserService $users): void
    {
        $this->resetMessages();

        if ($this->new_password !== $this->confirm_password) {
            $this->errorMessage = __('sintoniza.profile.passwords_dont_match');

            return;
        }

        try {
            $users->changePassword(Auth::user(), $this->current_password, $this->new_password);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->implode(' ');

            return;
        }

        $this->reset('current_password', 'new_password', 'confirm_password');
        $this->successMessage = __('sintoniza.profile.password_changed');
    }

    public function updateTimezone(UserService $users): void
    {
        $this->resetMessages();

        try {
            $users->updateTimezone(Auth::user(), $this->timezone);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->implode(' ');

            return;
        }

        $this->successMessage = __('sintoniza.profile.timezone_updated');
    }

    public function updateLanguage(UserService $users): void
    {
        $this->resetMessages();

        try {
            $users->updateLanguage(Auth::user(), $this->language);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->implode(' ');

            return;
        }

        $this->successMessage = __('sintoniza.profile.language_updated');
    }

    private function resetMessages(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function render(UserService $users)
    {
        $timezoneGroups = [];
        foreach (\DateTimeZone::listIdentifiers() as $identifier) {
            $group = explode('/', $identifier, 2)[0];
            $timezoneGroups[$group][] = $identifier;
        }

        return view('livewire.dashboard.profile', [
            'timezoneGroups' => $timezoneGroups,
            'userToken' => $users->getUserToken(Auth::user()),
        ])->layout('components.layouts.app')->title(__('sintoniza.general.profile'));
    }
}
