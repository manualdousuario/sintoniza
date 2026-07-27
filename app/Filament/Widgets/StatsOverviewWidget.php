<?php

namespace App\Filament\Widgets;

use App\Models\Episode;
use App\Models\Feed;
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
            'feeds' => Feed::count(),
            'episodes' => Episode::count(),
            'subs_7d' => Subscription::where('updated_at', '>=', now()->subDays(7))->count(),
        ]);

        return [
            Stat::make(__('sintoniza.statistics.registered_users'), $stats['users']),
            Stat::make(__('sintoniza.admin.active_subscriptions'), $stats['subscriptions']),
            Stat::make(__('sintoniza.admin.feeds'), $stats['feeds']),
            Stat::make(__('sintoniza.general.episodes'), $stats['episodes']),
            Stat::make(__('sintoniza.admin.new_subscriptions').' ('.__('sintoniza.admin.last_7_days').')', $stats['subs_7d']),
        ];
    }
}
