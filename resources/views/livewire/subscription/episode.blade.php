<div class="max-w-6xl mx-auto px-4 pb-8">
    @php
        $lastPosition = 0;
        foreach ($actions as $action) {
            $position = (int) ($action->data['position'] ?? 0);
            if ($action->action === 'play' && $position > 0) {
                $lastPosition = $position;
                break;
            }
        }
        $podcastUrl = $subscription->url ?? '';
        $playerI18n = [
            'loading' => __('sintoniza.player.loading'),
            'resuming' => __('sintoniza.player.resuming'),
            'ready' => __('sintoniza.player.ready'),
            'load_error' => __('sintoniza.player.load_error'),
            'playing' => __('sintoniza.player.playing'),
            'paused' => __('sintoniza.player.paused'),
            'stopped' => __('sintoniza.player.stopped'),
            'ended' => __('sintoniza.player.ended'),
            'play' => __('sintoniza.player.play'),
            'pause' => __('sintoniza.player.pause'),
        ];
        $episodeTitle = $episode->title ?? basename(strtok($episode->media_url, '?'));
    @endphp

    <div class="page-header">
        <a href="{{ route('subscription.show', $subscription->id) }}" class="btn-sm btn-secondary shrink-0">
            <x-tabler-arrow-left class="w-4 h-4" />
        </a>
        <div class="min-w-0">
            <h2 class="page-title">{{ $episodeTitle }}</h2>
            @if ($episode->published_at)
                <p class="m-0 text-sm text-slate-500 dark:text-slate-400">{{ $episode->published_at->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-8">
        @if ($episode->image_url)
            <div class="md:col-span-2">
                <a href="{{ $episode->image_url }}" target="_blank" rel="noopener" class="block">
                    <img class="rounded-xl w-full max-w-40 h-auto border border-slate-200 dark:border-slate-700"
                        src="{{ $episode->image_url }}" alt="" />
                </a>
            </div>
        @endif
        <div class="md:col-span-10">
            <div class="card mb-4" id="audio-player"
                data-episode-url="{{ $episode->media_url }}"
                data-podcast-url="{{ $podcastUrl }}"
                data-start-pos="{{ $lastPosition }}"
                data-total-dur="{{ (int) ($episode->duration ?? 0) }}"
                data-i18n="{{ json_encode($playerI18n, JSON_UNESCAPED_UNICODE) }}">
                <div class="p-5">
                    <div class="flex items-center gap-4">
                        <button id="btn-play-pause"
                            class="btn-primary rounded-full w-13 h-13 shrink-0 !p-0"
                            disabled aria-label="{{ $playerI18n['play'] }}">
                            <x-tabler-player-play-filled class="w-6 h-6" id="icon-play" />
                            <x-tabler-player-pause-filled class="w-6 h-6 hidden" id="icon-pause" />
                        </button>
                        <div class="grow">
                            <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400 mb-1">
                                <span id="player-time-current">--:--</span>
                                <span id="player-time-total">--:--</span>
                            </div>
                            <input type="range" id="player-progress" min="0" max="1000" value="0" step="1"
                                class="w-full accent-brand-500" disabled>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-0 mt-2" id="player-status">
                        {{ $playerI18n['loading'] }}
                    </p>
                </div>
            </div>

            @php $downloadName = $episode->title ?: basename(strtok($episode->media_url, '?')); @endphp
            <a href="{{ $episode->media_url }}"
                id="btn-download"
                data-url="{{ $episode->media_url }}"
                data-name="{{ $downloadName }}"
                download="{{ $downloadName }}"
                rel="noopener"
                class="btn-sm btn-secondary">
                <x-tabler-cloud-download class="w-4 h-4" id="btn-download-icon" />
                <x-tabler-loader-2 class="w-4 h-4 animate-spin hidden" id="btn-download-spinner" />
                <span id="btn-download-label">{{ __('sintoniza.general.download') }}</span>
            </a>

            @if ($episode->description)
                <div class="mt-4 text-sm text-slate-600 dark:text-slate-400 leading-relaxed episode-description">
                    {!! \App\Support\DescriptionFormatter::format($episode->description) !!}
                </div>
            @endif
        </div>
    </div>

    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-3">{{ __('sintoniza.general.history') }}</h3>

    @if ($actions->isEmpty())
        <div class="alert-info mb-6">{{ __('sintoniza.dashboard.no_info') }}</div>
    @else
        <div class="list-card mb-6">
            @foreach ($actions as $action)
                <div class="list-card-item">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($action->action === 'play')
                            <span class="badge-success"><x-tabler-player-play class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.played') }}</span>
                        @elseif ($action->action === 'download')
                            <span class="badge-primary"><x-tabler-download class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.downloaded') }}</span>
                        @elseif ($action->action === 'delete')
                            <span class="badge-danger"><x-tabler-trash class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.deleted') }}</span>
                        @else
                            <span class="badge-secondary"><x-tabler-devices class="w-3.5 h-3.5" /> {{ __('sintoniza.actions.unavailable') }}</span>
                        @endif
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('sintoniza.actions.from') }}</span>
                        @if ($action->device?->name)
                            <span class="badge-primary">{{ $action->device->name }}</span>
                        @else
                            <span class="badge-secondary"><x-tabler-devices class="w-3.5 h-3.5" /> {{ __('sintoniza.devices.unavailable') }}</span>
                        @endif
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('sintoniza.actions.on') }}</span>
                        <small class="text-slate-400">
                            <time datetime="{{ $action->changed_at->toIso8601String() }}">
                                {{ $action->changed_at->format('d/m/Y') }} {{ __('sintoniza.actions.at') }}
                                {{ $action->changed_at->format('H:i') }}
                            </time>
                        </small>
                        @if ($action->action === 'play' && !empty($action->data['position']))
                            <span class="text-slate-300 dark:text-slate-600">&middot;</span>
                            <small class="text-slate-400">{{ gmdate('H:i:s', (int) $action->data['position']) }}</small>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <script src="{{ asset('assets/js/howler.core.min.js') }}"></script>
    <script src="{{ asset('assets/js/player.js') }}"></script>
</div>
