<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function authenticate(string $username, string $password): ?User
    {
        $user = User::where('name', trim($username))->first();

        if (! $user || ! Hash::check(trim($password), $user->password)) {
            return null;
        }

        return $user;
    }

    /**
     * @throws ValidationException
     */
    public function register(string $name, string $password, string $email): User
    {
        $this->validateRegistration($name, $password, $email);

        $isFirstUser = User::count() === 0;

        return User::create([
            'name' => trim($name),
            'email' => trim($email),
            'password' => trim($password), // hashed by the model cast
            'is_admin' => $isFirstUser,
            'is_active' => true,
            'language' => App::getLocale(),
            'timezone' => 'UTC',
        ]);
    }

    public function canSubscribe(): bool
    {
        if (config('sintoniza.enable_subscriptions')) {
            return true;
        }

        return User::count() === 0;
    }

    /**
     * @throws ValidationException
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check(trim($currentPassword), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('sintoniza.messages.current_password_incorrect'),
            ]);
        }

        $user->update(['password' => $newPassword]);
    }

    /**
     * @throws ValidationException
     */
    public function updateTimezone(User $user, string $timezone): void
    {
        if (! in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            throw ValidationException::withMessages([
                'timezone' => __('sintoniza.messages.invalid_timezone'),
            ]);
        }

        $user->update(['timezone' => $timezone]);
    }

    /**
     * @throws ValidationException
     */
    public function updateLanguage(User $user, string $language): void
    {
        $normalized = str_replace('-', '_', $language);

        if (! in_array($normalized, (array) config('sintoniza.locales', ['en']), true)) {
            throw ValidationException::withMessages([
                'language' => __('sintoniza.messages.invalid_language'),
            ]);
        }

        $user->update(['language' => $normalized]);
    }

    /** gPodder API token: "username__{substr(sha1(password_hash), 0, 10)}". */
    public function getUserToken(User $user): string
    {
        return $user->name.'__'.$user->gpodderToken();
    }

    /** Validates a full "username__token" credential. */
    public function validateToken(string $username): ?User
    {
        $login = strtok($username, '__');
        strtok('');

        $user = User::where('name', $login)->first();

        if (! $user) {
            return null;
        }

        return hash_equals($this->getUserToken($user), $username) ? $user : null;
    }

    /**
     * @throws ValidationException
     */
    private function validateRegistration(string $name, string $password, string $email): void
    {
        $errors = [];

        if (trim($name) === '' || ! preg_match('/^\w[\w_-]+$/', $name)) {
            $errors['name'] = __('sintoniza.messages.invalid_username_format');
        }

        if ($name === 'current') {
            $errors['name'] = __('sintoniza.messages.blocked_username');
        }

        if (strlen(trim($password)) < 8) {
            $errors['password'] = __('sintoniza.messages.password_too_short');
        }

        if (! filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __('sintoniza.messages.invalid_email');
        }

        if ($errors === [] && User::where('name', trim($name))->exists()) {
            $errors['name'] = __('sintoniza.messages.username_exists');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
