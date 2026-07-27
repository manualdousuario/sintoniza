<div class="max-w-6xl mx-auto px-4 pb-8">
    <div class="page-header">
        <h2 class="page-title">{{ __('sintoniza.dashboard.add_podcast') }}</h2>
        <a href="{{ route('dashboard') }}" class="btn-sm btn-secondary">{{ __('sintoniza.general.subscriptions') }}</a>
    </div>

    @if ($errorMessage)
        <div class="alert-error mb-6" role="alert">{{ $errorMessage }}</div>
    @endif

    @if ($this->searchEnabled())
        <form wire:submit="search" class="mb-6">
            <div class="flex gap-2">
                <input type="text" wire:model="query" class="input"
                    placeholder="{{ __('sintoniza.dashboard.search_placeholder') }}" autofocus>
                <button type="submit" class="btn-primary shrink-0" wire:loading.attr="disabled" wire:target="search">
                    <x-tabler-search class="w-4 h-4" />
                    {{ __('sintoniza.dashboard.search') }}
                </button>
            </div>
        </form>

        @if ($searched && $query !== '')
            @if (empty($results))
                <div class="alert-warning mb-6">{{ __('sintoniza.dashboard.no_results') }}</div>
            @else
                <div class="list-card mb-6">
                    @foreach ($results as $feed)
                        @php
                            $feedUrl = $feed['url'] ?? '';
                            if ($feedUrl === '') {
                                continue;
                            }
                            $image = $feed['image'] ?? ($feed['artwork'] ?? '');
                            $title = $feed['title'] ?? $feedUrl;
                        @endphp
                        <div class="list-card-item">
                            <div class="flex gap-4 items-center">
                                @if ($image)
                                    <div class="shrink-0">
                                        <img class="rounded-lg border border-slate-200 dark:border-slate-700 w-20 h-20 object-cover"
                                            src="{{ $image }}" alt="" loading="lazy" />
                                    </div>
                                @endif
                                <div class="grow min-w-0">
                                    <h2 class="text-base font-semibold mb-1">{{ $title }}</h2>
                                    @if (!empty($feed['author']))
                                        <p class="text-sm text-slate-400 mb-1">{{ $feed['author'] }}</p>
                                    @endif
                                    @if (!empty($feed['description']))
                                        <p class="text-sm text-slate-500 dark:text-slate-400 m-0">
                                            {{ \Illuminate\Support\Str::limit(strip_tags((string) $feed['description']), 200) }}
                                        </p>
                                    @endif
                                </div>
                                <button type="button" class="btn-sm btn-primary shrink-0"
                                    wire:click="subscribe(@js($feedUrl))"
                                    wire:loading.attr="disabled">
                                    <x-tabler-plus class="w-4 h-4" />
                                    {{ __('sintoniza.dashboard.subscribe') }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        <hr class="border-slate-200 dark:border-slate-800 my-8">
    @endif

    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-4">{{ __('sintoniza.dashboard.add_by_url') }}</h3>
    <form wire:submit="subscribe">
        <div class="flex gap-2">
            <input type="url" wire:model="url" class="input" placeholder="https://example.com/feed.xml" required>
            <button type="submit" class="btn-primary shrink-0" wire:loading.attr="disabled" wire:target="subscribe">
                <x-tabler-plus class="w-4 h-4" />
                {{ __('sintoniza.dashboard.subscribe') }}
            </button>
        </div>
    </form>
</div>
