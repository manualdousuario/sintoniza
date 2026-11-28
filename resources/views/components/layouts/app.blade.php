@props(['title' => null])

@php
    $appName = config('app.name');
    $fullTitle = $title ? $title.' · '.$appName : $appName;
    $currentLocale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ __('sintoniza.general.site_description') }}">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg">
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
</head>

<body>
    <header class="site-header">
        <a href="/" class="site-name">{{ $appName }}</a>

        <nav class="site-nav" aria-label="{{ __('sintoniza.general.menu') }}">
            @auth
                <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                    {{ __('sintoniza.account.title') }}
                </a>
                @if (Auth::user()->is_admin)
                    <a href="/admin">{{ __('sintoniza.admin.admin') }}</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link-button">{{ __('sintoniza.general.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" @if (request()->routeIs('login')) aria-current="page" @endif>
                    {{ __('sintoniza.general.login') }}
                </a>
                <a href="{{ route('register') }}" @if (request()->routeIs('register')) aria-current="page" @endif>
                    {{ __('sintoniza.general.register') }}
                </a>
            @endauth
        </nav>
    </header>

    <main id="content">
        @foreach (['success' => 'notice-ok', 'message' => 'notice-ok', 'error' => 'notice-error'] as $key => $class)
            @if (session($key))
                <div class="notice {{ $class }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">
                    <p>{{ session($key) }}</p>
                </div>
            @endif
        @endforeach

        @if ($errors->any())
            <div class="notice notice-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="site-footer">
        <p>
            {{ $appName }} · <a href="https://github.com/manualdousuario/sintoniza" rel="noopener">{{ __('sintoniza.footer.source_code') }}</a>
        </p>

        @unless (request()->routeIs('dashboard'))
            <form method="POST" action="{{ route('language') }}">
                @csrf
                <label for="footer-language" class="visually-hidden">{{ __('sintoniza.general.language') }}</label>
                <select name="language" id="footer-language">
                    @foreach (config('sintoniza.locales', ['en']) as $locale)
                        <option value="{{ $locale }}" @selected($locale === $currentLocale)>
                            {{ __('sintoniza.language_names.'.$locale) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="button">{{ __('sintoniza.general.apply') }}</button>
            </form>
        @endunless
    </footer>
</body>

</html>
