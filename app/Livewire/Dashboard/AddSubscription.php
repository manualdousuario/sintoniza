<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Subscription;
use App\Services\FeedIndexer;
use App\Services\PodcastIndexClient;
use App\Support\Gpodder;
use App\Support\Url;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AddSubscription extends Component
{
    public string $query = '';

    public string $url = '';

    public array $results = [];

    public bool $searched = false;

    public ?string $errorMessage = null;

    public function search(): void
    {
        $this->errorMessage = null;
        $this->query = trim($this->query);
        $this->results = [];
        $this->searched = $this->query !== '';

        if ($this->query === '' || ! $this->searchEnabled()) {
            return;
        }

        try {
            $this->results = app(PodcastIndexClient::class)->searchByTerm($this->query);
        } catch (\Throwable) {
            $this->results = [];
        }
    }

    public function subscribe(FeedIndexer $feedIndexer, ?string $feedUrl = null)
    {
        $this->errorMessage = null;
        $url = trim((string) ($feedUrl ?? $this->url));

        if (! Gpodder::validateUrl($url)) {
            $this->errorMessage = __('sintoniza.messages.invalid_feed_url');

            return null;
        }

        $normalized = Url::normalizeFeed($url);

        if ($normalized === '') {
            $this->errorMessage = __('sintoniza.messages.invalid_feed_url');

            return null;
        }

        Subscription::subscribe((int) Auth::id(), $normalized, now());

        $feedIndexer->dispatchNew([$normalized]);

        session()->flash('success', __('sintoniza.messages.subscribed'));

        return $this->redirect(route('dashboard'));
    }

    public function searchEnabled(): bool
    {
        return app(PodcastIndexClient::class)->isConfigured();
    }

    public function render()
    {
        return view('livewire.dashboard.add-subscription')
            ->layout('components.layouts.app')
            ->title(__('sintoniza.dashboard.add_podcast'));
    }
}
