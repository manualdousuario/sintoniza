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
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}" />
</head>

<body>
    <div class="min-h-screen flex flex-col">
        <header class="bg-gradient-to-r from-slate-900 to-slate-800 border-b border-white/10 shadow-lg">
            <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-tabler-broadcast class="w-6 h-6 text-white" />
                    <a href="/" class="text-xl font-bold text-white tracking-tight">{{ config('app.name') }}</a>
                </div>

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

                    @guest
                        <a href="{{ route('login') }}" class="btn-sm btn-outline-dark">
                            <x-tabler-login class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ __('sintoniza.general.login') }}</span>
                        </a>
                        <a href="{{ route('register') }}" class="btn-sm btn-outline-dark">
                            <x-tabler-user-plus class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ __('sintoniza.general.register') }}</span>
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-sm btn-outline-dark">
                            <x-tabler-microphone class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ __('sintoniza.general.subscriptions') }}</span>
                        </a>
                    @endguest
                </div>
            </div>
        </header>

        <main class="grow flex flex-col">
            {{ $slot }}
        </main>

        <footer class="py-4 border-t border-slate-200 bg-white dark:bg-slate-900 dark:border-slate-800">
            <div class="max-w-6xl mx-auto px-4">
                <p class="m-0 text-sm text-slate-400">
                    {{ __('sintoniza.footer.with_love_by') }}
                    <a class="text-brand-600 hover:underline dark:text-brand-400" href="https://altendorfme.com/"
                        target="_blank" rel="noopener">altendorfme</a>
                </p>
            </div>
        </footer>
    </div>
</body>

</html>
