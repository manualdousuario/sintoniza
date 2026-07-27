<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class GpodderAuthService
{
    /** Why the last resolution failed. */
    public ?string $failureMessage = null;

    public function __construct(
        private UserService $users
    ) {}

    /** Resolves a "username__token" credential embedded in the URL. */
    public function resolveFromToken(string $usernameWithToken): ?User
    {
        $user = $this->users->validateToken($usernameWithToken);

        if (! $user) {
            $this->failureMessage = __('sintoniza.messages.invalid_gpodder_token');
        }

        return $user;
    }

    /** HTTP Basic auth; the username may carry a "__suffix" that is ignored. */
    public function resolveFromBasicAuth(?string $username, ?string $password): ?User
    {
        if (! $username || ! $password) {
            $this->failureMessage = __('sintoniza.messages.no_username_password');

            return null;
        }

        [$login] = explode('__', $username, 2);

        $user = User::where('name', $login)->first();

        if (! $user) {
            $this->failureMessage = __('sintoniza.messages.invalid_username');

            return null;
        }

        if (! Hash::check($password, $user->password)) {
            $this->failureMessage = __('sintoniza.messages.invalid_username_password');

            return null;
        }

        return $user;
    }

    /** Nextcloud app-password: "{token}:{sha1(password_hash . token)}". */
    public function resolveFromAppPassword(?string $username, ?string $password): ?User
    {
        if (! $username || ! $password) {
            $this->failureMessage = __('sintoniza.messages.no_username_password');

            return null;
        }

        $user = User::where('name', $username)->first();

        if (! $user) {
            $this->failureMessage = __('sintoniza.messages.invalid_username');

            return null;
        }

        $token = strtok($password, ':');
        $secret = strtok('');

        if ($secret === false || ! hash_equals(sha1($user->password.$token), $secret)) {
            $this->failureMessage = __('sintoniza.messages.invalid_username_password');

            return null;
        }

        return $user;
    }
}
