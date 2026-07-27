<?php

use App\Models\Episode;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'demo',
        'password' => 'password',
    ]);

    $feed = Feed::factory()->create();
    $this->subscription = Subscription::create([
        'user_id' => $this->user->id,
        'feed_id' => $feed->id,
        'url' => 'https://example.com/feed.xml',
    ]);
    $this->episode = Episode::factory()->create(['feed_id' => $feed->id]);
});

it('opens the episode page for the subscription owner', function () {
    $this->actingAs($this->user)
        ->get("/subscription/{$this->subscription->id}/episode/{$this->episode->id}")
        ->assertOk();
});

it('returns 404 for an episode not in the subscription feed', function () {
    $otherFeed = Feed::factory()->create();
    $otherEpisode = Episode::factory()->create(['feed_id' => $otherFeed->id]);

    $this->actingAs($this->user)
        ->get("/subscription/{$this->subscription->id}/episode/{$otherEpisode->id}")
        ->assertNotFound();
});
