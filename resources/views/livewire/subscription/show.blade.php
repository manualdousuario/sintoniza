<div class="max-w-6xl mx-auto px-4 pb-8">
    <div class="page-header">
        <a href="{{ route('dashboard') }}" class="btn-sm btn-secondary shrink-0">
            <x-tabler-arrow-left class="w-4 h-4" />
        </a>
        <h2 class="page-title">
            {{ $subscription->feed->title ?? __('sintoniza.general.subscriptions') }}
        </h2>
        <button type="button" class="btn-sm btn-danger-outline shrink-0"
            wire:click="unsubscribe"
            wire:confirm="{{ __('sintoniza.dashboard.unsubscribe_confirm') }}"
            wire:loading.attr="disabled"
            title="{{ __('sintoniza.dashboard.unsubscribe') }}">
            <x-tabler-trash class="w-4 h-4" />
        </button>
    </div>

    @if ($subscription->feed?->title && $subscription->feed?->image_url)
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-8">
            <div class="md:col-span-2">
                <img class="rounded-xl w-full max-w-40 h-auto border border-slate-200 dark:border-slate-700"
                    src="{{ $subscription->feed->image_url }}" alt="" />
            </div>
            <div class="md:col-span-10">
                @if ($subscription->url)
                    <p>
                        <a href="{{ $subscription->url }}" class="link break-all" target="_blank" rel="noopener">
                            {{ $subscription->url }}
                        </a>
                    </p>
                @endif
                @if ($subscription->feed?->description)
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ \Illuminate\Support\Str::limit(strip_tags((string) $subscription->feed->description), 500) }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-3">{{ __('sintoniza.general.episodes') }}</h3>

    @if (!$episodes || $episodes->isEmpty())
        <div class="alert-warning">{{ __('sintoniza.dashboard.no_info') }}</div>
    @else
        <div class="list-card mb-6">
            @foreach ($episodes as $episode)
                @php
                    $episodeTitle = $episode->title ?? basename(strtok($episode->media_url, '?'));
                    $episodeDuration = $episode->duration ? gmdate('H:i:s', (int) $episode->duration) : '—';
                    $episodeDate = $episode->published_at ? $episode->published_at->format('d/m/Y') : '';
                @endphp
                <div class="list-card-item">
                    <div class="flex gap-4">
                        @if ($episode->image_url)
                            <div class="shrink-0">
                                <img class="rounded-lg border border-slate-200 dark:border-slate-700 w-20 h-20 object-cover"
                                    src="{{ $episode->image_url }}" alt="" loading="lazy" />
                            </div>
                        @endif
                        <div class="grow min-w-0">
                            <h4 class="text-sm font-semibold mb-1">
                                <a class="link-dark"
                                    href="{{ route('subscription.episode', [$subscription->id, $episode->id]) }}">
                                    {{ $episodeTitle }}
                                </a>
                            </h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400 m-0">
                                {{ __('sintoniza.general.duration') }}: {{ $episodeDuration }}
                                @if ($episodeDate) &middot; {{ $episodeDate }} @endif
                            </p>
                            @if ($episode->last_action === 'play')
                                <div class="mt-1.5">
                                    <span class="badge-success"><x-tabler-player-play class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.played') }}</span>
                                </div>
                            @elseif ($episode->last_action === 'download')
                                <div class="mt-1.5">
                                    <span class="badge-primary"><x-tabler-download class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.downloaded') }}</span>
                                </div>
                            @elseif ($episode->last_action === 'delete')
                                <div class="mt-1.5">
                                    <span class="badge-danger"><x-tabler-trash class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.deleted') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $episodes->links() }}
    @endif
</div>
