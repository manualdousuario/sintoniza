<?php

use App\Models\Episode;
use App\Models\EpisodeAction;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use App\Services\FeedParser;
use Illuminate\Support\Facades\Http;

/** Calls the protected backfill and returns how many actions it relinked. */
function backfill(int $feedId): int
{
    return (function (int $id): int {
        return $this->backfillEpisodeActions($id);
    })->call(new FeedParser('https://feeds.example.com/probe.xml'), $feedId);
}

function mixedCaseRss(): string
{
    return <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0">
      <channel>
        <title>Mixed Case Podcast</title>
        <item>
          <title>Ep 1</title>
          <pubDate>Mon, 01 Jan 2024 10:00:00 +0000</pubDate>
          <enclosure url="https://cdn.example.com/MyShow/Ep01.mp3" length="32768000" type="audio/mpeg"/>
        </item>
      </channel>
    </rss>
    XML;
}

it('links an action to the episode when the feed publishes a mixed-case enclosure url', function () {
    Http::fake(['*' => Http::response(mixedCaseRss())]);

    $user = User::factory()->create();
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'url' => 'https://feeds.example.com/mixed.xml',
    ]);

    // Clients report the normalized URL — that is what store() persists.
    $action = EpisodeAction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'url' => 'https://cdn.example.com/myshow/ep01.mp3',
        'changed_at' => now(),
        'action' => 'play',
    ]);

    $this->artisan('sintoniza:fetch', ['urls' => ['https://feeds.example.com/mixed.xml']])
        ->assertExitCode(0);

    $episode = Episode::first();

    expect($episode->media_url)->toBe('https://cdn.example.com/MyShow/Ep01.mp3')
        ->and($episode->media_url_normalized)->toBe('https://cdn.example.com/myshow/ep01.mp3')
        ->and($action->fresh()->episode_id)->toBe($episode->id);
});

it('leaves already linked actions alone on the next sync', function () {
    Http::fake(['*' => Http::response(mixedCaseRss())]);

    $user = User::factory()->create();
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'url' => 'https://feeds.example.com/mixed.xml',
    ]);
    EpisodeAction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'url' => 'https://cdn.example.com/myshow/ep01.mp3',
        'changed_at' => now(),
        'action' => 'play',
    ]);

    $this->artisan('sintoniza:fetch', ['urls' => ['https://feeds.example.com/mixed.xml']])
        ->assertExitCode(0);

    $feedId = Feed::first()->id;

    expect(backfill($feedId))->toBe(0);
});

it('does not relink actions belonging to another feed sharing the media url', function () {
    $user = User::factory()->create();

    $feedA = Feed::create(['feed_url' => 'https://example.com/a.xml']);
    $feedB = Feed::create(['feed_url' => 'https://example.com/b.xml']);

    // The same show distributed under two unrelated feed URLs.
    $episodeA = Episode::create(['feed_id' => $feedA->id, 'media_url' => 'https://cdn.example.com/ep1.mp3']);
    Episode::create(['feed_id' => $feedB->id, 'media_url' => 'https://cdn.example.com/ep1.mp3']);

    $subscriptionA = Subscription::create([
        'user_id' => $user->id,
        'feed_id' => $feedA->id,
        'url' => 'https://example.com/a.xml',
    ]);
    $action = EpisodeAction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscriptionA->id,
        'episode_id' => $episodeA->id,
        'url' => 'https://cdn.example.com/ep1.mp3',
        'changed_at' => now(),
        'action' => 'play',
    ]);

    expect(backfill($feedB->id))->toBe(0)
        ->and($action->fresh()->episode_id)->toBe($episodeA->id);
});

it('settles on the lowest episode id when a feed holds duplicate media urls', function () {
    $user = User::factory()->create();

    $feed = Feed::create(['feed_url' => 'https://example.com/dup.xml']);

    // Distinct under the (feed_id, media_url) unique key, identical once normalized.
    $first = Episode::create(['feed_id' => $feed->id, 'media_url' => 'https://cdn.example.com/ep1.mp3']);
    Episode::create(['feed_id' => $feed->id, 'media_url' => 'https://cdn.example.com/ep1.mp3/']);

    $subscription = Subscription::create([
        'user_id' => $user->id,
        'feed_id' => $feed->id,
        'url' => 'https://example.com/dup.xml',
    ]);
    $action = EpisodeAction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'url' => 'https://cdn.example.com/ep1.mp3',
        'changed_at' => now(),
        'action' => 'play',
    ]);

    backfill($feed->id);
    expect($action->fresh()->episode_id)->toBe($first->id);

    // Repeated syncs keep pointing at the same episode instead of flip-flopping.
    backfill($feed->id);
    expect($action->fresh()->episode_id)->toBe($first->id);
});

it('links uploaded actions to episodes stored with a mixed-case media url', function () {
    $user = User::factory()->create([
        'name' => 'testuser',
        'email' => 'testuser@example.test',
        'password' => 'secret-password',
    ]);

    $feed = Feed::create(['feed_url' => 'https://example.com/feed.xml']);
    $episode = Episode::create([
        'feed_id' => $feed->id,
        'media_url' => 'https://cdn.example.com/MyShow/Ep01.mp3',
    ]);
    Subscription::create([
        'user_id' => $user->id,
        'feed_id' => $feed->id,
        'url' => 'https://example.com/feed.xml',
    ]);

    $payload = [[
        'podcast' => 'https://example.com/feed.xml',
        'episode' => 'https://cdn.example.com/MyShow/Ep01.mp3',
        'action' => 'play',
        'timestamp' => '2026-07-20T10:00:00Z',
    ]];

    $this->call('POST', '/api/2/episodes/testuser.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
    ], json_encode($payload))->assertStatus(200);

    expect(EpisodeAction::first()->episode_id)->toBe($episode->id);
});
