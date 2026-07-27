<div class="max-w-6xl mx-auto px-4 pb-8">
    <div class="page-header">
        <h2 class="page-title">{{ __('sintoniza.general.latest_updates') }}</h2>
    </div>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 inline-flex items-center gap-1.5">
        <x-tabler-info-circle class="w-4 h-4" />
        {{ __('sintoniza.general.latest_updates_info') }}
    </p>

    @if ($actions->isEmpty())
        <div class="alert-warning">{{ __('sintoniza.dashboard.no_info') }}</div>
    @else
        <div class="list-card mb-6">
            @foreach ($actions as $action)
                @php
                    $url = strtok(basename($action->url), '?');
                    $title = $action->episode->title ?? $url;
                    $duration = $action->episode?->duration ? gmdate('H:i:s', (int) $action->episode->duration) : null;
                @endphp
                <div class="list-card-item">
                    <div class="flex flex-wrap items-center gap-2 pb-2">
                        @if ($action->action === 'play')
                            <span class="badge-success"><x-tabler-player-play class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.played') }}</span>
                        @elseif ($action->action === 'download')
                            <span class="badge-primary"><x-tabler-download class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.downloaded') }}</span>
                        @elseif ($action->action === 'delete')
                            <span class="badge-danger"><x-tabler-trash class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.deleted') }}</span>
                        @else
                            <span class="badge-secondary"><x-tabler-devices class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.unavailable') }}</span>
                        @endif
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('sintoniza.actions.on') }}</span>
                        @if ($action->device?->name)
                            <span class="badge-primary">{{ $action->device->name }}</span>
                        @else
                            <span class="badge-secondary"><x-tabler-devices class="w-3.5 h-3.5" /> {{ __('sintoniza.devices.unavailable') }}</span>
                        @endif
                        <small class="text-slate-400">
                            <time datetime="{{ $action->changed_at->toIso8601String() }}">{{ $action->changed_at->format('d/m/Y H:i') }}</time>
                        </small>
                    </div>
                    <div class="flex gap-4">
                        @if ($action->episode?->image_url)
                            <div class="shrink-0">
                                <img class="rounded-lg border border-slate-200 dark:border-slate-700 w-20 h-20 object-cover"
                                    src="{{ $action->episode->image_url }}" alt="" loading="lazy" />
                            </div>
                        @endif
                        <div class="min-w-0">
                            @if ($action->episode_id)
                                <a class="link-dark"
                                    href="{{ route('subscription.episode', [$action->subscription_id, $action->episode_id]) }}">
                                    {{ $title }}
                                </a>
                            @else
                                <span class="link-dark">{{ $title }}</span>
                            @endif
                            @if ($duration)
                                <br>
                                <span class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('sintoniza.general.duration') }}: {{ $duration }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $actions->links() }}
    @endif
</div>
