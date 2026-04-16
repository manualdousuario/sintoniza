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

    public function fetchAndSync(string $url): ?Feed
    {
        try {
            $feed    = new Feed($url);
            $fetched = false;

            if ($this->podcastIndexClient && PODCAST_INDEX_USE_AS_PRIMARY) {
                $fetched = $feed->fetchFromPodcastIndex($this->podcastIndexClient);

                if (!$fetched) {
                    $this->logger->warning('PodcastIndex fetch failed', ['url' => $url]);
                }
            }

            if (!$fetched && (!$this->podcastIndexClient || !PODCAST_INDEX_USE_AS_PRIMARY || PODCAST_INDEX_FALLBACK_TO_RSS)) {
                $fetched = $feed->fetch($this->client);
            }

            if (!$fetched) {
                $this->logger->warning('Failed to fetch feed', ['url' => $url]);
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

            return null;
        }
    }

    public function updateAllStaleFeeds(bool $cli = false): int
    {
        $sql = 'SELECT s.id AS subscription, s.url,
            GREATEST(COALESCE(MAX(a.changed), 0), s.changed) AS changed
            FROM subscriptions s
                LEFT JOIN episodes_actions a ON a.subscription = s.id
                LEFT JOIN feeds f ON f.id = s.feed
            WHERE f.last_fetch IS NULL
                OR f.last_fetch < s.changed
                OR f.last_fetch < COALESCE(a.changed, 0)
            GROUP BY s.id, s.url, s.changed';

        @ini_set('max_execution_time', '3600');

        $count = 0;

        foreach ($this->db->iterate($sql) as $row) {
            @set_time_limit(30);

            if ($cli) {
                printf("Atualizando %s\n", $row->url);
            }

            $feed = $this->fetchAndSync($row->url);

            if ($feed) {
                $count++;
            }
        }

        return $count;
    }

    public function getForSubscription(int $subscriptionId): ?Feed
    {
        $data = $this->db->firstRow(
            'SELECT f.* FROM subscriptions s INNER JOIN feeds f ON f.id = s.feed WHERE s.id = ?',
            $subscriptionId
        );

        if (!$data) {
            return null;
        }

        $feed = new Feed($data->feed_url ?? '');
        $feed->load($data);

        return $feed;
    }
}
