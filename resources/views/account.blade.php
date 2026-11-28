@php
    $typeLabels = [
        'mobile' => __('sintoniza.devices.mobile'),
        'desktop' => __('sintoniza.devices.desktop'),
        'laptop' => __('sintoniza.devices.desktop'),
        'server' => __('sintoniza.devices.server'),
        'other' => __('sintoniza.devices.other'),
    ];
    $userTimezone = $user->timezone ?: 'UTC';
@endphp
<x-layouts.app :title="__('sintoniza.account.title')">
    @if ($okToken)
        <div class="notice notice-ok" role="status">
            <p>{{ __('sintoniza.messages.login_success') }}</p>
        </div>
    @endif

    <h1>{{ __('sintoniza.account.connect_title') }}</h1>
    <p>{{ __('sintoniza.account.connect_intro') }}</p>

    <dl class="credentials">
        <dt>{{ __('sintoniza.account.server') }}</dt>
        <dd><code>{{ $serverUrl }}</code></dd>
        <dt>{{ __('sintoniza.general.username') }}</dt>
        <dd><code>{{ $user->name }}</code></dd>
        <dt>{{ __('sintoniza.general.password') }}</dt>
        <dd class="small muted">{{ __('sintoniza.account.password_hint') }}</dd>
        <dt>{{ __('sintoniza.account.token') }}</dt>
        <dd><code>{{ $token }}</code></dd>
    </dl>

    <ul class="small">
        <li>{{ __('sintoniza.account.howto_gpodder') }}</li>
        <li>{{ __('sintoniza.account.howto_token') }}</li>
        <li>{{ __('sintoniza.account.howto_nextcloud') }}</li>
    </ul>

    <h2 id="devices">{{ __('sintoniza.general.devices') }}</h2>
    @if ($devices->isEmpty())
        <p class="muted">{{ __('sintoniza.account.no_devices') }}</p>
    @else
        <ul class="rows">
            @foreach ($devices as $device)
                @php
                    $type = $typeLabels[$device->data['type'] ?? ''] ?? null;
                @endphp
                <li>
                    <span class="main">{{ $device->name ?: $device->identifier }}</span>
                    <span class="meta">
                        {{ $device->identifier }}{{ $type ? ' · '.$type : '' }}
                        · {{ __('sintoniza.account.last_seen', ['date' => $device->updated_at?->timezone($userTimezone)->format('Y-m-d') ?? '—']) }}
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    <h2 id="subscriptions">{{ __('sintoniza.general.subscriptions') }}</h2>
    @if ($subscriptions->isEmpty())
        <p class="muted">{{ __('sintoniza.account.no_subscriptions') }}</p>
    @else
        <p class="small">
            {{ trans_choice('sintoniza.account.subscription_count', $subscriptions->count(), ['count' => $subscriptions->count()]) }}
            · <a href="/subscriptions/{{ $user->name }}.opml">{{ __('sintoniza.account.export_opml') }}</a>
        </p>
        <ul class="rows rows-action">
            @foreach ($subscriptions as $subscription)
                <li>
                    <span class="main url">{{ $subscription->url }}</span>
                    <form method="POST" action="{{ route('account.unsubscribe', $subscription->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-button danger"
                            aria-label="{{ __('sintoniza.account.remove_named', ['url' => $subscription->url]) }}">
                            {{ __('sintoniza.account.remove') }}
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
        <p class="small muted">{{ __('sintoniza.account.remove_note') }}</p>
    @endif

    <h2 id="settings">{{ __('sintoniza.account.preferences') }}</h2>
    <form method="POST" action="{{ route('account.preferences') }}">
        @csrf
        <div class="field">
            <label for="language">{{ __('sintoniza.general.language') }}</label>
            <select name="language" id="language">
                @foreach (config('sintoniza.locales', ['en']) as $locale)
                    <option value="{{ $locale }}" @selected($locale === ($user->language ?: 'en'))>
                        {{ __('sintoniza.language_names.'.$locale) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="timezone">{{ __('sintoniza.account.timezone') }}</label>
            <select name="timezone" id="timezone">
                @foreach ($timezones as $timezone)
                    <option value="{{ $timezone }}" @selected($timezone === $userTimezone)>{{ $timezone }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="button">{{ __('sintoniza.general.save') }}</button>
    </form>

    <h2 id="password">{{ __('sintoniza.profile.change_password') }}</h2>
    <form method="POST" action="{{ route('account.password') }}">
        @csrf
        <div class="field">
            <label for="current_password">{{ __('sintoniza.profile.current_password') }}</label>
            <input type="password" name="current_password" id="current_password" required
                autocomplete="current-password" @error('current_password') aria-invalid="true" @enderror>
        </div>
        <div class="field">
            <label for="new_password">{{ __('sintoniza.profile.new_password') }}</label>
            <input type="password" name="new_password" id="new_password" minlength="8" required
                autocomplete="new-password" aria-describedby="new-password-hint"
                @error('new_password') aria-invalid="true" @enderror>
            <p class="hint" id="new-password-hint">{{ __('sintoniza.profile.min_password_length') }}</p>
        </div>
        <div class="field">
            <label for="new_password_confirmation">{{ __('sintoniza.profile.confirm_password') }}</label>
            <input type="password" name="new_password_confirmation" id="new_password_confirmation" minlength="8"
                required autocomplete="new-password">
        </div>
        <p class="small muted">{{ __('sintoniza.account.password_token_note') }}</p>
        <button type="submit" class="button">{{ __('sintoniza.profile.change_password') }}</button>
    </form>
</x-layouts.app>
