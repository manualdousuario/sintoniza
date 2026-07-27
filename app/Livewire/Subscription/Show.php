<?php

declare(strict_types=1);

namespace App\Livewire\Subscription;

use App\Models\Episode;
use App\Models\EpisodeAction;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Subscription $subscription;

    public function mount(int $id): void
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->with('feed')
            ->find($id);

        abort_if($subscription === null, 404);

        $this->subscription = $subscription;
    }

    public function unsubscribe()
    {
        $this->subscription->delete();

        session()->flash('success', __('sintoniza.messages.unsubscribed'));

        return $this->redirect(route('dashboard'));
    }

    public function render()
    {
        $episodes = null;

        if ($this->subscription->feed_id) {
            $userId = Auth::id();

            $episodes = Episode::query()
                ->where('feed_id', $this->subscription->feed_id)
                ->addSelect([
                    'last_action' => EpisodeAction::select('action')
                        ->whereColumn('episode_id', 'episodes.id')
                        ->where('user_id', $userId)
                        ->orderByDesc('changed_at')
                        ->limit(1),
                ])
                ->orderByDesc('published_at')
                ->paginate(20);
        }

        return view('livewire.subscription.show', [
            'episodes' => $episodes,
        ])->layout('components.layouts.app')->title($this->subscription->feed->title ?? __('sintoniza.general.subscriptions'));
    }
}
