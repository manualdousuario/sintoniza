<?php

declare(strict_types=1);

namespace App\Livewire\Subscription;

use App\Models\Episode as EpisodeModel;
use App\Models\EpisodeAction;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Episode extends Component
{
    public Subscription $subscription;

    public EpisodeModel $episode;

    public function mount(int $id, int $episodeId): void
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->with('feed')
            ->find($id);

        abort_if($subscription === null, 404);

        $episode = $subscription->feed_id
            ? EpisodeModel::where('feed_id', $subscription->feed_id)->find($episodeId)
            : null;

        abort_if($episode === null, 404);

        $this->subscription = $subscription;
        $this->episode = $episode;
    }

    public function render()
    {
        $actions = EpisodeAction::query()
            ->where('user_id', Auth::id())
            ->where('subscription_id', $this->subscription->id)
            ->where('episode_id', $this->episode->id)
            ->with('device')
            ->orderByDesc('changed_at')
            ->get();

        return view('livewire.subscription.episode', [
            'actions' => $actions,
        ])->layout('components.layouts.app')->title($this->episode->title ?? __('sintoniza.general.episodes'));
    }
}
