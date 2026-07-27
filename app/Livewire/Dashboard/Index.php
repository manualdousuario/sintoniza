<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function unsubscribe(int $id)
    {
        Subscription::where('user_id', Auth::id())->where('id', $id)->first()?->delete();

        session()->flash('success', __('sintoniza.messages.unsubscribed'));

        return $this->redirect(route('dashboard'));
    }

    public function render()
    {
        $subscriptions = Subscription::query()
            ->where('user_id', Auth::id())
            ->with('feed')
            ->withCount('episodeActions')
            ->withMax('episodeActions as last_action_at', 'changed_at')
            ->orderByRaw('COALESCE(last_action_at, updated_at) DESC')
            ->paginate(20);

        return view('livewire.dashboard.index', [
            'subscriptions' => $subscriptions,
            'okToken' => request()->query->has('oktoken'),
        ])->layout('components.layouts.app')->title(__('sintoniza.general.subscriptions'));
    }
}
