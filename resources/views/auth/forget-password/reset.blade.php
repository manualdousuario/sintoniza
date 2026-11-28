<x-layouts.app :title="__('sintoniza.general.reset_password')">
    <div class="narrow">
        <h1>{{ __('sintoniza.general.reset_password') }}</h1>

        @if (session('message'))
            <p><a href="{{ route('login') }}" class="button">{{ __('sintoniza.general.login') }}</a></p>
        @else
            <form method="post" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="field">
                    <label for="new_password">{{ __('sintoniza.general.new_password') }}</label>
                    <input type="password" name="new_password" id="new_password" minlength="8" required autofocus
                        autocomplete="new-password" aria-describedby="new-password-hint">
                    <p class="hint" id="new-password-hint">{{ __('sintoniza.profile.min_password_length') }}</p>
                </div>
                <button type="submit" class="button">{{ __('sintoniza.general.reset_password') }}</button>
            </form>
        @endif
    </div>
</x-layouts.app>
