<x-layouts.app :title="__('sintoniza.general.register')">
    <div class="narrow">
        <h1>{{ __('sintoniza.general.register') }}</h1>

        @if ($disabled)
            <div class="notice" role="status">
                <p>{{ __('sintoniza.messages.subscriptions_disabled') }}</p>
            </div>
        @else
            <form method="post" action="{{ route('register') }}">
                @csrf
                <div class="field">
                    <label for="username">{{ __('sintoniza.general.username') }}</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus
                        autocomplete="username" autocapitalize="none" spellcheck="false"
                        aria-describedby="username-hint" @error('name') aria-invalid="true" @enderror>
                    <p class="hint" id="username-hint">{{ __('sintoniza.auth.username_hint') }}</p>
                </div>
                <div class="field">
                    <label for="password">{{ __('sintoniza.general.password') }}</label>
                    <input type="password" name="password" id="password" minlength="8" required
                        autocomplete="new-password" aria-describedby="password-hint"
                        @error('password') aria-invalid="true" @enderror>
                    <p class="hint" id="password-hint">{{ __('sintoniza.profile.min_password_length') }}</p>
                </div>
                <div class="field">
                    <label for="email">{{ __('sintoniza.general.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                        aria-describedby="email-hint" @error('email') aria-invalid="true" @enderror>
                    <p class="hint" id="email-hint">{{ __('sintoniza.auth.email_hint') }}</p>
                </div>
                <button type="submit" class="button">{{ __('sintoniza.auth.create_account') }}</button>
            </form>
        @endif

        <p class="small muted">
            {{ __('sintoniza.auth.have_account') }} <a href="{{ route('login') }}">{{ __('sintoniza.general.login') }}</a>
        </p>
    </div>
</x-layouts.app>
