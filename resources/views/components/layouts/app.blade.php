@props(['title' => null])

@php
    $fullTitle = $title ? config('app.name').' | '.$title : config('app.name');
    $languageLabels = __('sintoniza.language_names');
    $currentLocale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $fullTitle }}</title>
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="{{ $fullTitle }}" />
    <meta name="description" content="{{ __('sintoniza.general.site_description') }}" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $fullTitle.' - '.__('sintoniza.general.podcast_sync') }}" />
    <meta property="og:description" content="{{ __('sintoniza.general.site_description') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}" />
</head>

<body>
    <nav class="bg-gradient-to-r from-slate-900 to-slate-800 border-b border-white/10 shadow-lg"
        x-data="{ open: false }">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex items-center justify-between gap-3 py-3">
                <div class="flex items-center gap-2 min-w-0">
                    <x-tabler-broadcast class="w-6 h-6 text-white shrink-0" />
                    <a href="/" class="text-xl font-bold text-white tracking-tight truncate">{{ config('app.name') }}</a>
                </div>

                @auth
                    <div class="hidden lg:flex items-center gap-1 text-sm">
                        <a href="{{ route('dashboard') }}"
                            class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.general.subscriptions') }}
                        </a>
                        <a href="{{ route('dashboard.add') }}"
                            class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('dashboard.add') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.dashboard.add_podcast') }}
                        </a>
                        <a href="{{ route('dashboard.latest-updates') }}"
                            class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('dashboard.latest-updates') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.general.latest_updates') }}
                        </a>
                        <a href="{{ route('dashboard.devices') }}"
                            class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('dashboard.devices') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.general.devices') }}
                        </a>
                        <a href="{{ route('dashboard.profile') }}"
                            class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('dashboard.profile') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.general.profile') }}
                        </a>
                    </div>
                @endauth

                <div class="flex items-center gap-2">
                    {{-- Language dropdown --}}
                    <div class="relative" x-data="{ langOpen: false }" @keydown.escape.window="langOpen = false">
                        <button type="button" class="btn-sm btn-outline-dark" @click="langOpen = !langOpen"
                            :aria-expanded="langOpen.toString()" title="{{ __('sintoniza.general.language') }}">
                            <x-tabler-language class="w-4 h-4" />
                            <span class="hidden md:inline">{{ $languageLabels[$currentLocale] ?? $currentLocale }}</span>
                        </button>
                        <div x-show="langOpen" x-cloak @click.outside="langOpen = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-40 rounded-xl bg-white border border-slate-200 shadow-lg py-1 z-50 dark:bg-slate-800 dark:border-slate-700">
                            @foreach (config('sintoniza.locales', ['en']) as $locale)
                                <form method="POST" action="{{ route('language') }}">
                                    @csrf
                                    <input type="hidden" name="language" value="{{ $locale }}">
                                    <button type="submit"
                                        class="block w-full text-left px-4 py-2 text-sm transition
                                            {{ $locale === $currentLocale
                                                ? 'bg-brand-50 text-brand-700 font-semibold dark:bg-brand-900/40 dark:text-brand-300'
                                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                                        {{ $languageLabels[$locale] ?? $locale }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    @auth
                        @if (Auth::user()->is_admin)
                            <a href="/admin" class="btn-sm btn-outline-dark" title="{{ __('sintoniza.general.administration') }}">
                                <x-tabler-shield-lock class="w-4 h-4" />
                                <span class="hidden md:inline">{{ __('sintoniza.admin.admin') }}</span>
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn-sm btn-outline-dark" title="{{ __('sintoniza.general.logout') }}">
                                <x-tabler-door-exit class="w-4 h-4" />
                            </button>
                        </form>
                        <button type="button" class="btn-sm btn-outline-dark lg:hidden" @click="open = !open"
                            :aria-expanded="open.toString()">
                            <x-tabler-menu-2 class="w-4 h-4" />
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="btn-sm btn-outline-dark">
                            <x-tabler-login class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ __('sintoniza.general.login') }}</span>
                        </a>
                        <a href="{{ route('register') }}" class="btn-sm btn-outline-dark">
                            <x-tabler-user-plus class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ __('sintoniza.general.register') }}</span>
                        </a>
                    @endauth
                </div>
            </div>

            @auth
                {{-- Mobile menu --}}
                <div x-show="open" x-cloak x-transition class="lg:hidden pb-3 -mx-1">
                    <div class="flex flex-col gap-1 text-sm">
                        <a href="{{ route('dashboard') }}"
                            class="px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.general.subscriptions') }}
                        </a>
                        <a href="{{ route('dashboard.add') }}"
                            class="px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard.add') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.dashboard.add_podcast') }}
                        </a>
                        <a href="{{ route('dashboard.latest-updates') }}"
                            class="px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard.latest-updates') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.general.latest_updates') }}
                        </a>
                        <a href="{{ route('dashboard.devices') }}"
                            class="px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard.devices') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.general.devices') }}
                        </a>
                        <a href="{{ route('dashboard.profile') }}"
                            class="px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard.profile') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            {{ __('sintoniza.general.profile') }}
                        </a>
                    </div>
                </div>
            @endauth
        </div>
    </nav>

    <main class="grow">
        @php
            $flashSuccess = session('success');
            $flashError = session('error');
            $flashMessage = session('message');
        @endphp
        @if ($flashSuccess || $flashError || $flashMessage)
            <div class="max-w-6xl mx-auto px-4 mt-4 space-y-3">
                @if ($flashSuccess)
                    <div class="alert-success" role="alert">{{ $flashSuccess }}</div>
                @endif
                @if ($flashError)
                    <div class="alert-error" role="alert">{{ $flashError }}</div>
                @endif
                @if ($flashMessage)
                    <div class="alert-success" role="alert">{{ $flashMessage }}</div>
                @endif
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="py-4 border-t border-slate-200 mt-auto bg-white dark:bg-slate-900 dark:border-slate-800">
        <div class="max-w-6xl mx-auto px-4">
            <p class="m-0 text-sm text-slate-400">
                {{ __('sintoniza.footer.with_love_by') }}
                <a class="text-brand-600 hover:underline dark:text-brand-400" href="https://altendorfme.com/"
                    target="_blank" rel="noopener">altendorfme</a>
            </p>
        </div>
    </footer>
</body>

</html>
