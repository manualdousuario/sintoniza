<x-layouts.app :title="__('sintoniza.general.login')">
    <div class="narrow">
        <h1>{{ __('sintoniza.general.login') }}</h1>

        @if ($hasToken)
            <div class="notice" role="status">
                <p>{{ __('sintoniza.messages.app_requesting_access') }}</p>
            </div>
        @endif

        <form method="post" action="{{ route('login', request()->query()) }}">
            @csrf
            <div class="field">
                <label for="login">{{ __('sintoniza.general.username') }}</label>
                <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                    autocomplete="username" autocapitalize="none" spellcheck="false">
            </div>
            <div class="field">
                <label for="password">{{ __('sintoniza.general.password') }}</label>
                <input type="password" name="password" id="password" required autocomplete="current-password">
            </div>
            <div class="actions">
                <button type="submit" class="button">{{ __('sintoniza.general.login') }}</button>
                <a href="{{ route('password.request') }}" class="small">{{ __('sintoniza.general.forgot_password') }}</a>
            </div>
        </form>

        @unless ($hasToken)
            <p class="small muted">
                {{ __('sintoniza.auth.no_account') }} <a href="{{ route('register') }}">{{ __('sintoniza.general.register') }}</a>
            </p>
        @endunless
    </div>
</x-layouts.app>
