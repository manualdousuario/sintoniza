<x-layouts.app :title="__('sintoniza.general.forgot_password')">
    <div class="narrow">
        <h1>{{ __('sintoniza.general.forgot_password') }}</h1>
        <p>{{ __('sintoniza.auth.reset_intro') }}</p>

        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label for="email">{{ __('sintoniza.general.email') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    autocomplete="email">
            </div>
            <div class="actions">
                <button type="submit" class="button">{{ __('sintoniza.general.send_reset_link') }}</button>
                <a href="{{ route('login') }}" class="small">{{ __('sintoniza.auth.back_to_login') }}</a>
            </div>
        </form>
    </div>
</x-layouts.app>
