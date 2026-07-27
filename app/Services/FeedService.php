<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Feed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FeedService
{
    public function __construct(
        private readonly PodcastIndexClient $podcastIndex,
    ) {}

    /**
     * $source is 'auto' (Podcast Index first when configured as primary),
     * 'podcastindex' (RSS only as a configured fallback) or 'rss'.
     * Returns true when the feed was fetched — a 304 counts — and synced.
     */
    public function fetchAndSync(string $url, string $source = 'auto'): bool
    {
        try {
            $parser = new FeedParser($url);

            if ($parser->feed_url === null || $parser->feed_url === '') {
                Log::warning('Failed to fetch feed', ['url' => $url, 'reason' => 'invalid url']);

                return false;
            }

            $usePrimary = (bool) config('sintoniza.podcastindex.use_as_primary', false);
            $fallbackToRss = (bool) config('sintoniza.podcastindex.fallback_to_rss', true);

            $fetched = false;
            $piAttempted = false;

            $tryPodcastIndex = $source === 'podcastindex' || ($source === 'auto' && $usePrimary);

            if ($tryPodcastIndex && $this->podcastIndex->isConfigured()) {
                $piAttempted = true;
                $fetched = $parser->fetchFromPodcastIndex($this->podcastIndex);

                if (! $fetched) {
                    Log::warning('PodcastIndex fetch failed', ['url' => $url]);
                }
            }

            // Only an explicit 'podcastindex' source can forbid the RSS path,
            // and only once Podcast Index was actually tried.
            $tryRss = $source !== 'podcastindex' || ! $piAttempted || $fallbackToRss;

            if (! $fetched && $tryRss) {
                $this->primeHttpCache($parser);
                $fetched = $parser->fetch();
            }

            if (! $fetched) {
                Log::warning('Failed to fetch feed', ['url' => $url]);

                return false;
            }

            $parser->sync();
            Log::info('Feed synced successfully', ['url' => $url]);

            return true;
        } catch (Throwable $e) {
            Log::error('Error syncing feed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Fetches every subscribed feed that is due, oldest first, and returns how
     * many synced. Default batch size: max(min_batch, total feeds / divisor).
     */
    public function updateAllStaleFeeds(?int $maxFeeds = null): int
    {
        @ini_set('max_execution_time', '3600');

        if ($maxFeeds === null) {
            $totalFeeds = Feed::query()->count();
            $maxFeeds = max(
                (int) config('sintoniza.feeds.min_batch', 100),
                (int) ceil($totalFeeds / max(1, (int) config('sintoniza.feeds.batch_divisor', 12)))
            );
        }

        $now = now();

        // Distinct subscribed feed URLs due for a fetch, never-fetched first.
        $urls = DB::table('subscriptions as s')
            ->leftJoin('feeds as f', 'f.id', '=', 's.feed_id')
            ->whereNull('s.deleted_at')
            ->where(function ($query) use ($now): void {
                $query->whereNull('f.next_fetch_at')
                    ->orWhere('f.next_fetch_at', '<=', $now);
            })
            ->groupBy('s.url')
            ->select('s.url')
            ->selectRaw('MIN(f.next_fetch_at) AS due_at')
            ->selectRaw('MIN(s.id) AS sid')
            ->orderBy('due_at')
            ->orderBy('sid')
            ->limit($maxFeeds)
            ->pluck('url');

        Log::info('Updating stale feeds', ['batch' => $maxFeeds, 'queued' => $urls->count()]);

        $count = 0;
        $timeLimit = (int) config('sintoniza.feeds.per_feed_time_limit', 30);

        foreach ($urls as $url) {
            if ($timeLimit > 0) {
                @set_time_limit($timeLimit);
            }

            if ($this->fetchAndSync($url)) {
                $count++;
            }

            gc_collect_cycles();
        }

        return $count;
    }

    /**
     * Prime the parser's HTTP validators from the stored feed row so the
     * conditional GET can actually produce a 304 (alias-aware lookup).
     */
    private function primeHttpCache(FeedParser $parser): void
    {
        $feed = Feed::query()->where('feed_url', $parser->feed_url)->first()
            ?? Feed::query()->whereIn(
                'id',
                DB::table('feed_aliases')->where('url', $parser->feed_url)->select('feed_id')
            )->first();

        if ($feed) {
            $parser->etag = $feed->etag;
            $parser->last_modified = $feed->last_modified;
        }
    }
}
