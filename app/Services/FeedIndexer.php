<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\FetchFeed;
use App\Support\Url;
use Illuminate\Support\Facades\DB;

/**
 * Dispatches fetch jobs for newly subscribed feeds that are not yet known
 * (present in neither feeds nor feed_aliases).
 */
class FeedIndexer
{
    /**
     * @param  string[]  $urls
     */
    public function dispatchNew(array $urls): void
    {
        if (! config('sintoniza.immediate_feed_fetch', true)) {
            return;
        }

        $new = [];

        foreach ($urls as $url) {
            $normalized = Url::normalizeFeed((string) $url);

            if ($normalized === '' || isset($new[$normalized])) {
                continue;
            }

            if ($this->exists($normalized)) {
                continue;
            }

            $new[$normalized] = true;
        }

        foreach (array_keys($new) as $url) {
            FetchFeed::dispatch($url);
        }
    }

    private function exists(string $normalizedUrl): bool
    {
        return (bool) DB::scalar(
            'SELECT 1 FROM feeds WHERE feed_url = ?
             UNION
             SELECT 1 FROM feed_aliases WHERE url = ?
             LIMIT 1',
            [$normalizedUrl, $normalizedUrl]
        );
    }
}
