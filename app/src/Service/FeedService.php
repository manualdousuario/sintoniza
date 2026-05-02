<?php

declare(strict_types=1);

namespace Sintoniza\Service;

use GuzzleHttp\Client;
use Monolog\Logger;
use Sintoniza\Database\DB;
use Sintoniza\Feed\Feed;
use Sintoniza\Feed\PodcastIndexClient;
use Sintoniza\Repository\FeedRepository;

class FeedService
{
    public function __construct(
        private DB $db,
        private FeedRepository $feedRepository,
        private Logger $logger,
        private Client $client,
        private ?PodcastIndexClient $podcastIndexClient = null
    ) {}

    public function fetchAndSync(string $url, ?string &$source = null): ?Feed
    {
        $source = null;

        try {
            $feed         = new Feed($url);
            $fetched      = false;
            $piAttempted  = false;

            if ($this->podcastIndexClient && PODCAST_INDEX_USE_AS_PRIMARY) {
                $piAttempted = true;
                $fetched     = $feed->fetchFromPodcastIndex($this->podcastIndexClient);

                if ($fetched) {
                    $source = 'podcastindex';
                } else {
                    $this->logger->warning('PodcastIndex fetch failed', ['url' => $url]);
                }
            }

            if (!$fetched && (!$this->podcastIndexClient || !PODCAST_INDEX_USE_AS_PRIMARY || PODCAST_INDEX_FALLBACK_TO_RSS)) {
                $fetched = $feed->fetch($this->client);

                if ($fetched) {
                    if ($feed->notModified) {
                        $source = 'rss-not-modified';
                    } else {
                        $source = $piAttempted ? 'rss-fallback' : 'rss';
                    }
                }
            }

            if (!$fetched) {
                $this->logger->warning('Failed to fetch feed', ['url' => $url]);
                $this->feedRepository->recordFailure($url);
                return null;
            }

            $feed->sync($this->db);
            $this->logger->info('Feed synced successfully', ['url' => $url]);

            return $feed;
        } catch (\Exception $e) {
            $this->logger->error('Error syncing feed', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
            $this->feedRepository->recordFailure($url);

            return null;
        }
    }

    public function updateAllStaleFeeds(bool $cli = false, ?int $maxFeeds = null): int
    {
        @ini_set('max_execution_time', '3600');

        $now = time();

        if ($maxFeeds === null) {
            $activeFeeds = (int) $this->db->firstColumn(
                'SELECT COUNT(*) FROM feeds WHERE active = 1'
            );
            $maxFeeds = max(100, (int) ceil($activeFeeds / 12));
        }

        $sql = 'SELECT s.id AS subscription, s.url,
            COALESCE(f.next_fetch_at, 0) AS next_fetch_at
            FROM subscriptions s
                LEFT JOIN feeds f ON f.id = s.feed
            WHERE s.deleted = 0
                AND (f.active IS NULL OR f.active = 1)
                AND (f.next_fetch_at IS NULL OR f.next_fetch_at <= ?)
            GROUP BY s.url
            ORDER BY next_fetch_at ASC, s.id ASC
            LIMIT ?';

        $rows  = $this->db->all($sql, $now, $maxFeeds);
        $count = 0;

        if ($cli) {
            printf("Processing up to %d feed(s) in this run (queue: %d)\n", $maxFeeds, count($rows));
            @flush();
        }

        foreach ($rows as $row) {
            @set_time_limit(30);

            if ($cli) {
                printf("Updating %s\n", $row->url);
                @flush();
            }

            $source = null;
            $feed   = $this->fetchAndSync($row->url, $source);

            if ($cli) {
                $label = match ($source) {
                    'podcastindex'      => 'Podcast Index',
                    'rss'               => 'RSS',
                    'rss-not-modified'  => 'RSS (304 Not Modified — skipped)',
                    'rss-fallback'      => 'RSS (fallback Podcast Index failed)',
                    default             => 'Failed',
                };
                printf("  -> Source: %s\n", $label);
                @flush();
            }

            if ($feed) {
                $count++;
            }

            unset($feed, $source);
            gc_collect_cycles();
        }

        return $count;
    }

}
