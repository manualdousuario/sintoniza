<div class="max-w-6xl mx-auto px-4 pb-8">
    <div class="page-header">
        <h2 class="page-title">{{ __('sintoniza.general.devices') }}</h2>
    </div>

    @if ($devices->isNotEmpty())
        <div class="list-card">
            @foreach ($devices as $device)
                @php
                    $data = $device->data ?? [];
                    $icons = [
                        'mobile' => 'device-mobile',
                        'other' => 'device-laptop',
                        'desktop' => 'device-desktop',
                    ];
                    $icon = $icons[$data['type'] ?? ''] ?? 'devices';
                    $typeLabels = [
                        'mobile' => __('sintoniza.devices.mobile'),
                        'desktop' => __('sintoniza.devices.desktop'),
                        'other' => __('sintoniza.devices.desktop'),
                    ];
                @endphp
                <div class="list-card-item py-3">
                    <div class="flex items-center gap-3">
                        <x-dynamic-component :component="'tabler-'.$icon" class="w-7 h-7 text-slate-400 shrink-0" />
                        <div class="min-w-0">
                            <strong class="text-slate-800 dark:text-slate-100">{{ $device->name ?? $device->identifier }}</strong>
                            <div class="text-xs text-slate-400 flex flex-wrap items-center gap-x-2">
                                <span class="font-mono">{{ $device->identifier }}</span>
                                @if (isset($typeLabels[$data['type'] ?? '']))
                                    <span>&middot; {{ $typeLabels[$data['type']] }}</span>
                                @endif
                                @if (isset($data['subscriptions']))
                                    <span>&middot; {{ (int) $data['subscriptions'] }} {{ __('sintoniza.general.subscriptions') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert-warning">{{ __('sintoniza.dashboard.no_info') }}</div>
    @endif
</div>
