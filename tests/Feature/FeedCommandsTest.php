<?php

use App\Models\Episode;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function fakeRss(): string
{
    return <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0">
      <channel>
        <title>Fake Podcast</title>
        <link>https://fake.example.com</link>
        <description>Fake.</description>
        <language>en</language>
        <item>
          <title>Ep 1</title>
          <pubDate>Mon, 01 Jan 2024 10:00:00 +0000</pubDate>
          <enclosure url="https://cdn.example.com/1.mp3" length="32768000" type="audio/mpeg"/>
        </item>
        <item>
          <title>Ep 2</title>
          <pubDate>Mon, 08 Jan 2024 10:00:00 +0000</pubDate>
          <enclosure url="https://cdn.example.com/2.mp3" length="16384000" type="audio/mpeg"/>
        </item>
      </channel>
    </rss>
    XML;
}

// ----------------------------------------------------------- sintoniza:fetch

it('requires at least one url', function () {
    $this->artisan('sintoniza:fetch')
        ->expectsOutputToContain('Usage: sintoniza:fetch')
        ->assertExitCode(2);
});

it('fetches and syncs a feed url', function () {
    Http::fake(['*' => Http::response(fakeRss(), 200, ['ETag' => '"v1"'])]);

    $this->artisan('sintoniza:fetch', ['urls' => ['https://feeds.example.com/fake.xml']])
        ->expectsOutputToContain('Fetching https://feeds.example.com/fake.xml')
        ->expectsOutputToContain('  -> Synced')
        ->assertExitCode(0);

    $feed = Feed::where('feed_url', 'https://feeds.example.com/fake.xml')->first();

    expect($feed)->not->toBeNull()
        ->and($feed->title)->toBe('Fake Podcast')
        ->and($feed->etag)->toBe('"v1"')
        ->and($feed->last_fetched_at)->not->toBeNull()
        ->and($feed->next_fetch_at->getTimestamp() - $feed->last_fetched_at->getTimestamp())
        ->toBe(86400);

    expect(Episode::where('feed_id', $feed->id)->count())->toBe(2);
});

it('reports a failed fetch', function () {
    Http::fake(['*' => Http::response('Server Error', 500)]);

    $this->artisan('sintoniza:fetch', ['urls' => ['https://feeds.example.com/broken.xml']])
        ->expectsOutputToContain('  -> Failed')
        ->assertExitCode(1);

    expect(Feed::count())->toBe(0);
});

// ---------------------------------------------------- sintoniza:update-feeds

it('runs on an empty database', function () {
    $this->artisan('sintoniza:update-feeds')
        ->expectsOutputToContain('Done: 0 feed(s)')
        ->assertExitCode(0);
});

it('processes a stale subscribed feed', function () {
    Http::fake(['*' => Http::response(fakeRss())]);

    $user = User::factory()->create();
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'url' => 'https://feeds.example.com/fake.xml',
    ]);

    $this->artisan('sintoniza:update-feeds', ['--max-feeds' => 5])
        ->expectsOutputToContain('Done: 1 feed(s)')
        ->assertExitCode(0);

    $feed = Feed::where('feed_url', 'https://feeds.example.com/fake.xml')->first();

    expect($feed)->not->toBeNull()
        ->and($feed->title)->toBe('Fake Podcast')
        ->and($feed->next_fetch_at)->not->toBeNull();

    // The subscription was re-pointed to the synced feed.
    expect($subscription->fresh()->feed_id)->toBe($feed->id);

    expect(Episode::where('feed_id', $feed->id)->count())->toBe(2);
});

it('keeps feed metadata on 304 not modified', function () {
    Http::fake(['*' => Http::response('', 304)]);

    $user = User::factory()->create();
    $feed = Feed::create([
        'feed_url' => 'https://feeds.example.com/fake.xml',
        'title' => 'Original Title',
        'description' => 'Keep me',
        'etag' => '"v1"',
        'last_fetched_at' => now()->subDays(2),
        'next_fetch_at' => now()->subDay(),
    ]);
    Subscription::create([
        'user_id' => $user->id,
        'feed_id' => $feed->id,
        'url' => 'https://feeds.example.com/fake.xml',
    ]);

    $this->artisan('sintoniza:update-feeds')
        ->expectsOutputToContain('Done: 1 feed(s)')
        ->assertExitCode(0);

    $feed->refresh();

    expect($feed->title)->toBe('Original Title')
        ->and($feed->description)->toBe('Keep me')
        ->and($feed->next_fetch_at->isFuture())->toBeTrue();

    expect(Episode::where('feed_id', $feed->id)->count())->toBe(0);
});

it('upserts only episodes newer than the stored watermark on re-fetch', function () {
    // Second fetch: same media URLs, one updated title, one brand-new episode.
    $updated = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0">
      <channel>
        <title>Fake Podcast</title>
        <item>
          <title>Ep 1 (old, must be skipped)</title>
          <pubDate>Mon, 01 Jan 2024 10:00:00 +0000</pubDate>
          <enclosure url="https://cdn.example.com/1.mp3" length="32768000" type="audio/mpeg"/>
        </item>
        <item>
          <title>Ep 2 Revised</title>
          <pubDate>Mon, 15 Jan 2024 10:00:00 +0000</pubDate>
          <enclosure url="https://cdn.example.com/2.mp3" length="16384000" type="audio/mpeg"/>
        </item>
        <item>
          <title>Ep 3</title>
          <pubDate>Mon, 22 Jan 2024 10:00:00 +0000</pubDate>
          <enclosure url="https://cdn.example.com/3.mp3" length="16384000" type="audio/mpeg"/>
        </item>
      </channel>
    </rss>
    XML;

    Http::fake(['*' => Http::sequence()
        ->push(fakeRss(), 200, ['ETag' => '"v1"'])
        ->push($updated)]);

    $this->artisan('sintoniza:fetch', ['urls' => ['https://feeds.example.com/fake.xml']])
        ->assertExitCode(0);

    $feed = Feed::where('feed_url', 'https://feeds.example.com/fake.xml')->first();
    expect(Episode::where('feed_id', $feed->id)->count())->toBe(2);

    $this->artisan('sintoniza:fetch', ['urls' => ['https://feeds.example.com/fake.xml']])
        ->assertExitCode(0);

    $episodes = Episode::where('feed_id', $feed->id)->orderBy('published_at')->get();

    expect($episodes)->toHaveCount(3)
        // old episode kept its original title (below the watermark: skipped)
        ->and($episodes[0]->title)->toBe('Ep 1')
        // newer pubdate with an existing media_url: upsert updated the title
        ->and($episodes[1]->title)->toBe('Ep 2 Revised')
        ->and($episodes[2]->title)->toBe('Ep 3');
});
