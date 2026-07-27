<x-layouts.guest>
    {{-- ============================================================ Hero --}}
    <div class="relative overflow-hidden mb-12"
        style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
        <div class="absolute inset-0 pointer-events-none"
            style="background:
                radial-gradient(circle at 30% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 20%, rgba(16, 185, 129, 0.1) 0%, transparent 40%);">
        </div>

        <div class="relative max-w-6xl mx-auto px-4 py-20">
            <div class="grid items-center gap-10 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight tracking-tight mb-5">
                        {{ __('sintoniza.home.hero_title_1') }}<br>
                        <span class="bg-gradient-to-r from-brand-400 to-emerald-300 bg-clip-text text-transparent">
                            {{ __('sintoniza.home.hero_title_2') }}
                        </span>
                    </h1>
                    <p class="text-lg text-slate-400 max-w-xl leading-relaxed mb-8">
                        {{ config('app.name') }} {{ __('sintoniza.home.hero_subtitle') }}
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center gap-2 px-8 py-3 rounded-xl font-semibold text-white bg-gradient-to-b from-brand-400 to-brand-500 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-500/40">
                            <x-tabler-user-plus class="w-5 h-5" />
                            {{ __('sintoniza.general.register') }}
                        </a>
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center gap-2 px-8 py-3 rounded-xl font-semibold text-slate-200 border border-white/25 transition hover:bg-white/10 hover:border-white/40 hover:text-white">
                            <x-tabler-login class="w-5 h-5" />
                            {{ __('sintoniza.general.login') }}
                        </a>
                    </div>
                </div>

                <div class="hidden lg:block lg:col-span-5 lg:col-start-8">
                    <div class="rounded-2xl p-5 border border-white/10 bg-white/5 backdrop-blur">
                        <div class="flex items-center gap-2.5 pb-3 mb-3 border-b border-white/10">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <span class="text-slate-500 text-xs ml-2">{{ config('app.name') }} —
                                {{ __('sintoniza.home.syncing_header') }}</span>
                        </div>

                        @php
                            $syncItems = [
                                ['icon' => 'device-mobile', 'color' => 'text-indigo-400', 'bg' => 'bg-indigo-500/15', 'label' => 'AntennaPod (Android)', 'status' => __('sintoniza.home.synced'), 'statusClass' => 'text-emerald-400 bg-emerald-400/10'],
                                ['icon' => 'device-laptop', 'color' => 'text-amber-400', 'bg' => 'bg-amber-500/15', 'label' => 'gPodder (Desktop)', 'status' => __('sintoniza.home.synced'), 'statusClass' => 'text-emerald-400 bg-emerald-400/10'],
                                ['icon' => 'device-desktop', 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/15', 'label' => 'Cardo (Windows)', 'status' => __('sintoniza.home.synced'), 'statusClass' => 'text-emerald-400 bg-emerald-400/10'],
                                ['icon' => 'device-desktop', 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/15', 'label' => 'YourPods (iOS)', 'status' => __('sintoniza.home.synced'), 'statusClass' => 'text-emerald-400 bg-emerald-400/10'],
                                ['icon' => 'device-tablet', 'color' => 'text-violet-400', 'bg' => 'bg-violet-500/15', 'label' => 'Kasts (Linux)', 'status' => __('sintoniza.home.syncing'), 'statusClass' => 'text-amber-400 bg-amber-400/10'],
                            ];
                        @endphp

                        @foreach ($syncItems as $item)
                            <div class="flex items-center gap-3 py-2 {{ !$loop->first ? 'border-t border-white/5' : '' }}">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $item['bg'] }}">
                                    <x-dynamic-component :component="'tabler-'.$item['icon']" class="w-4 h-4 {{ $item['color'] }}" />
                                </div>
                                <span class="flex-1 text-sm text-slate-300 truncate">{{ $item['label'] }}</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $item['statusClass'] }}">
                                    {{ $item['status'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 p-3 rounded-xl border border-white/10 bg-white/5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 text-xs inline-flex items-center gap-1">
                                <x-tabler-clock class="w-3.5 h-3.5" />{{ __('sintoniza.home.last_sync_label') }}
                            </span>
                            <span class="text-emerald-400 text-xs font-semibold">{{ __('sintoniza.home.just_now') }}</span>
                        </div>
                        <div class="mt-2 flex gap-2 flex-wrap">
                            <span class="text-xs text-slate-400 bg-white/5 px-2.5 py-1 rounded-md">{{ __('sintoniza.home.hero_stat_subscriptions') }}</span>
                            <span class="text-xs text-slate-400 bg-white/5 px-2.5 py-1 rounded-md">{{ __('sintoniza.home.hero_stat_episodes') }}</span>
                            <span class="text-xs text-slate-400 bg-white/5 px-2.5 py-1 rounded-md">{{ __('sintoniza.home.hero_stat_devices') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4">
        {{-- ==================================================== Features --}}
        <div class="mb-12">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-4xl font-extrabold tracking-tight text-brand-600 dark:text-brand-400">
                    {{ sprintf(__('sintoniza.home.why_use'), config('app.name')) }}
                </h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['icon' => 'repeat', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-indigo-500/10', 'title' => __('sintoniza.home.feature_realtime_title'), 'desc' => __('sintoniza.home.feature_realtime_desc')],
                        ['icon' => 'shield-check', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'title' => __('sintoniza.home.feature_privacy_title'), 'desc' => __('sintoniza.home.feature_privacy_desc')],
                        ['icon' => 'plug', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50 dark:bg-amber-500/10', 'title' => __('sintoniza.home.feature_gpodder_title'), 'desc' => __('sintoniza.home.feature_gpodder_desc')],
                        ['icon' => 'device-mobile', 'color' => 'text-violet-500', 'bg' => 'bg-violet-50 dark:bg-violet-500/10', 'title' => __('sintoniza.home.feature_multidevice_title'), 'desc' => __('sintoniza.home.feature_multidevice_desc')],
                        ['icon' => 'chart-line', 'color' => 'text-red-500', 'bg' => 'bg-red-50 dark:bg-red-500/10', 'title' => __('sintoniza.home.feature_history_title'), 'desc' => __('sintoniza.home.feature_history_desc')],
                        ['icon' => 'code', 'color' => 'text-sky-500', 'bg' => 'bg-sky-50 dark:bg-sky-500/10', 'title' => __('sintoniza.home.feature_opensource_title'), 'desc' => __('sintoniza.home.feature_opensource_desc')],
                    ];
                @endphp

                @foreach ($features as $feature)
                    <div class="card p-8 h-full transition duration-200 hover:-translate-y-1 hover:border-brand-300 hover:shadow-xl hover:shadow-brand-500/10 dark:hover:border-brand-700">
                        <div class="w-13 h-13 rounded-2xl flex items-center justify-center mb-5 p-3.5 {{ $feature['bg'] }}">
                            <x-dynamic-component :component="'tabler-'.$feature['icon']" class="w-7 h-7 {{ $feature['color'] }}" />
                        </div>
                        <div class="text-base font-bold text-slate-800 mb-2 dark:text-slate-100">{{ $feature['title'] }}</div>
                        <p class="text-sm text-slate-500 leading-relaxed m-0 dark:text-slate-400">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- =================================================== How it works --}}
        <div class="mb-12 py-8 px-6 md:px-8 rounded-3xl bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 dark:from-slate-900 dark:to-slate-900/60 dark:border-slate-800">
            <div class="grid items-center gap-8 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <div class="inline-block text-sm font-bold text-brand-600 uppercase tracking-widest mb-1 dark:text-brand-400">
                        {{ __('sintoniza.home.how_it_works') }}
                    </div>
                    <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight text-brand-600 mb-3 dark:text-brand-400">
                        {{ __('sintoniza.home.setup_title') }}
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('sintoniza.home.setup_subtitle') }}</p>
                </div>
                <div class="lg:col-span-8">
                    <div class="flex flex-col gap-3">
                        @foreach ([
                            ['n' => '1', 'title' => __('sintoniza.home.step_1_title'), 'desc' => __('sintoniza.home.step_1_desc')],
                            ['n' => '2', 'title' => __('sintoniza.home.step_2_title'), 'desc' => __('sintoniza.home.step_2_desc')],
                            ['n' => '3', 'title' => __('sintoniza.home.step_3_title'), 'desc' => __('sintoniza.home.step_3_desc')],
                        ] as $step)
                            <div class="flex items-start gap-3 p-4 bg-white rounded-xl border border-slate-200 dark:bg-slate-900 dark:border-slate-800">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-b from-brand-400 to-brand-600 text-white font-extrabold text-sm flex items-center justify-center shrink-0">
                                    {{ $step['n'] }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-sm dark:text-slate-100">{{ $step['title'] }}</div>
                                    <div class="text-sm text-slate-500 mt-1 dark:text-slate-400">{{ $step['desc'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================ Compatible apps --}}
        <div class="mb-12">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-4xl font-extrabold tracking-tight text-brand-600 dark:text-brand-400">
                    {{ __('sintoniza.home.compatible_apps') }}
                </h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $clients = [
                        ['name' => 'AntennaPod', 'version' => '3.5.0', 'url' => 'https://github.com/AntennaPod/AntennaPod', 'icon' => 'device-mobile', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50 dark:bg-amber-500/10', 'platforms' => [['icon' => 'brand-android', 'label' => 'Android']]],
                        ['name' => 'Cardo', 'version' => '1.90', 'url' => 'https://cardo-podcast.github.io/', 'icon' => 'device-desktop', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'platforms' => [['icon' => 'brand-windows', 'label' => 'Windows'], ['icon' => 'brand-apple', 'label' => 'macOS'], ['icon' => 'brand-ubuntu', 'label' => 'Linux']]],
                        ['name' => 'Kasts', 'version' => '21.88', 'url' => 'https://invent.kde.org/multimedia/kasts', 'icon' => 'playlist', 'color' => 'text-violet-500', 'bg' => 'bg-violet-50 dark:bg-violet-500/10', 'platforms' => [['icon' => 'brand-windows', 'label' => 'Windows'], ['icon' => 'brand-android', 'label' => 'Android'], ['icon' => 'brand-ubuntu', 'label' => 'Linux']]],
                        ['name' => 'YourPods', 'version' => '2.0.2', 'url' => 'https://apps.apple.com/us/app/yourpods-podcast-player/id6757721236', 'icon' => 'player-play', 'color' => 'text-teal-600', 'bg' => 'bg-teal-50 dark:bg-teal-500/10', 'platforms' => [['icon' => 'brand-apple', 'label' => 'iOS']]],
                    ];
                @endphp

                @foreach ($clients as $client)
                    <a href="{{ $client['url'] }}" target="_blank" rel="noopener" class="block h-full">
                        <div class="card p-6 h-full transition duration-200 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-500/10 dark:hover:border-brand-700">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 {{ $client['bg'] }}">
                                    <x-dynamic-component :component="'tabler-'.$client['icon']" class="w-6 h-6 {{ $client['color'] }}" />
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-100">{{ $client['name'] }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">v{{ $client['version'] }}</div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($client['platforms'] as $platform)
                                    <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 text-xs font-semibold px-2 py-1 rounded-md dark:bg-slate-800 dark:text-slate-300">
                                        <x-dynamic-component :component="'tabler-'.$platform['icon']" class="w-3.5 h-3.5" />
                                        {{ $platform['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.guest>
