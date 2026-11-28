@php
    $apps = [
        ['name' => 'AntennaPod', 'version' => '3.5.0', 'platforms' => 'Android', 'url' => 'https://github.com/AntennaPod/AntennaPod'],
        ['name' => 'Cardo', 'version' => '1.90', 'platforms' => 'Windows, macOS, Linux', 'url' => 'https://cardo-podcast.github.io/'],
        ['name' => 'gPodder', 'version' => '3.11.4', 'platforms' => 'Windows, macOS, Linux', 'url' => 'https://gpodder.github.io/'],
        ['name' => 'Kasts', 'version' => '21.88', 'platforms' => 'Windows, Android, Linux', 'url' => 'https://invent.kde.org/multimedia/kasts'],
        ['name' => 'YourPods', 'version' => '2', 'platforms' => 'iOS', 'url' => 'https://apps.apple.com/us/app/yourpods-podcast-player/id6757721236'],
    ];
@endphp
<x-layouts.app>
    <h1>{{ __('sintoniza.home.title') }}</h1>
    <p class="lead">{{ sprintf(__('sintoniza.home.intro'), config('app.name')) }}</p>
    <p>{{ __('sintoniza.home.scope') }}</p>

    <div class="actions">
        <a href="{{ route('register') }}" class="button">{{ __('sintoniza.auth.create_account') }}</a>
        <a href="{{ route('login') }}">{{ __('sintoniza.auth.have_account_login') }}</a>
    </div>

    <h2>{{ __('sintoniza.home.how_title') }}</h2>
    <ol>
        <li>{{ __('sintoniza.home.step_1') }}</li>
        <li>{{ __('sintoniza.home.step_2') }}</li>
        <li>{{ __('sintoniza.home.step_3') }}</li>
    </ol>

    <h2>{{ __('sintoniza.home.apps_title') }}</h2>
    <ul class="rows">
        @foreach ($apps as $app)
            <li>
                <span class="main"><a href="{{ $app['url'] }}" rel="noopener">{{ $app['name'] }}</a></span>
                <span class="meta">{{ $app['version'] }}+ · {{ $app['platforms'] }}</span>
            </li>
        @endforeach
    </ul>
    <p class="small muted">{{ __('sintoniza.home.apps_note') }}</p>

    <h2>{{ __('sintoniza.home.not_title') }}</h2>
    <p>{{ __('sintoniza.home.not_body') }}</p>
</x-layouts.app>
