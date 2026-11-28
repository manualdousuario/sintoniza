<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use App\Models\EpisodeAction;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = Cache::remember('admin:stats', 300, fn () => [
            'users' => User::count(),
            'subscriptions' => Subscription::count(),
            'devices' => Device::count(),
            'actions' => EpisodeAction::count(),
            'subs_7d' => Subscription::where('updated_at', '>=', now()->subDays(7))->count(),
        ]);

        return [
            Stat::make(__('sintoniza.statistics.registered_users'), $stats['users']),
            Stat::make(__('sintoniza.admin.active_subscriptions'), $stats['subscriptions']),
            Stat::make(__('sintoniza.general.devices'), $stats['devices']),
            Stat::make(__('sintoniza.admin.episode_actions'), $stats['actions']),
            Stat::make(__('sintoniza.admin.new_subscriptions').' ('.__('sintoniza.admin.last_7_days').')', $stats['subs_7d']),
        ];
    }
}
