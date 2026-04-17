<?php

declare(strict_types=1);

namespace Sintoniza\Repository;

use Sintoniza\Database\DB;

class FeedRepository
{
    public function __construct(private DB $db) {}

    public function recordFailure(string $url, int $maxFailures = 3): void
    {
        $this->db->simple(
            'INSERT INTO feeds (feed_url, last_fetch, fetch_failures, active) VALUES (?, 0, 1, 1)
             ON DUPLICATE KEY UPDATE
                id             = LAST_INSERT_ID(id),
                active         = CASE WHEN fetch_failures + 1 >= ? THEN 0 ELSE active END,
                fetch_failures = fetch_failures + 1',
            $url,
            $maxFailures
        );

        $feedId = (int) $this->db->lastInsertId();

        if ($feedId > 0) {
            $this->db->simple(
                'UPDATE subscriptions SET feed = ? WHERE url = ? AND feed IS NULL',
                $feedId,
                $url
            );
        }
    }
}
