<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\EpisodeAction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class LatestUpdates extends Component
{
    use WithPagination;

    private const PER_PAGE = 20;

    private const COUNT_TTL = 30;

    public function render()
    {
        $userId = Auth::id();
        $since = now()->subDays(7);

        $total = Cache::remember(
            "dashboard:actions_count:{$userId}",
            self::COUNT_TTL,
            fn () => $this->baseQuery($since)->count()
        );

        $page = $this->getPage();
        $items = $this->baseQuery($since)
            ->with(['episode', 'device'])
            ->orderByDesc('changed_at')
            ->forPage($page, self::PER_PAGE)
            ->get();

        $actions = new LengthAwarePaginator($items, $total, self::PER_PAGE, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);

        return view('livewire.dashboard.latest-updates', [
            'actions' => $actions,
        ])->layout('components.layouts.app')->title(__('sintoniza.general.latest_updates'));
    }

    private function baseQuery(\DateTimeInterface $since)
    {
        return EpisodeAction::query()
            ->where('user_id', Auth::id())
            ->whereHas('subscription')
            ->where('changed_at', '>=', $since);
    }
}
