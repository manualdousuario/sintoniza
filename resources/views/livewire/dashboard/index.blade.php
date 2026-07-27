<div class="max-w-6xl mx-auto px-4 pb-8">
    <div class="page-header">
        <h2 class="page-title">{{ __('sintoniza.general.subscriptions') }}</h2>
        <a href="{{ route('dashboard.add') }}" class="btn-sm btn-primary">
            <x-tabler-plus class="w-4 h-4" />
            {{ __('sintoniza.dashboard.add_podcast') }}
        </a>
        <a href="/subscriptions/{{ Auth::user()->name }}.opml" target="_blank" rel="noopener"
            class="btn-sm btn-secondary" title="{{ __('sintoniza.dashboard.opml_feed') }}">
            <x-tabler-rss class="w-4 h-4" />
        </a>
    </div>

    @if ($okToken)
        <div class="alert-success mb-6" role="alert">{{ __('sintoniza.messages.login_success') }}</div>
    @endif

    @if ($subscriptions->isEmpty())
        <div class="alert-warning">{{ __('sintoniza.dashboard.no_info') }}</div>
    @else
        <div class="list-card mb-6">
            @foreach ($subscriptions as $subscription)
                @php
                    $title = $subscription->feed?->title
                        ?? str_replace(['http://', 'https://'], '', $subscription->url);
                    $lastChange = $subscription->last_action_at
                        ? \Illuminate\Support\Carbon::parse($subscription->last_action_at)
                        : $subscription->updated_at;
                @endphp
                <div class="list-card-item">
                    <div class="flex gap-4 items-start">
                        @if ($subscription->feed?->image_url)
                            <div class="shrink-0">
                                <img class="rounded-lg border border-slate-200 dark:border-slate-700 w-20 h-20 object-cover"
                                    src="{{ $subscription->feed->image_url }}" alt="" loading="lazy" />
                            </div>
                        @endif
                        <div class="grow min-w-0">
                            <h2 class="text-base font-semibold mb-1">
                                <a class="link-dark" href="{{ route('subscription.show', $subscription->id) }}">
                                    {{ $title }}
                                </a>
                            </h2>
                            @if ($subscription->feed?->description)
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">
                                    {{ \Illuminate\Support\Str::limit(strip_tags((string) $subscription->feed->description), 200) }}
                                </p>
                            @endif
                            <p class="text-xs text-slate-400 m-0">
                                <strong>{{ __('sintoniza.dashboard.last_update') }}</strong>:
                                <time datetime="{{ $lastChange->toIso8601String() }}" class="whitespace-nowrap">
                                    {{ $lastChange->format('d/m/Y H:i') }}
                                </time>
                            </p>
                        </div>
                        <button type="button" class="btn-sm btn-danger-outline shrink-0"
                            wire:click="unsubscribe({{ $subscription->id }})"
                            wire:confirm="{{ __('sintoniza.dashboard.unsubscribe_confirm') }}"
                            wire:loading.attr="disabled"
                            title="{{ __('sintoniza.dashboard.unsubscribe') }}">
                            <x-tabler-trash class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $subscriptions->links() }}
    @endif
</div>
