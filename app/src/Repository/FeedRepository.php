<?php

declare(strict_types=1);

namespace Sintoniza\Repository;

use Sintoniza\Database\DB;
use Sintoniza\Library\Url;

class FeedRepository
{
    public function __construct(private DB $db) {}

    public function findById(int $id): ?\stdClass
    {
        return $this->db->firstRow('SELECT * FROM feeds WHERE id = ?', $id);
    }

    public function setActive(int $id, bool $active): void
    {
        if ($active) {
            $this->db->simple(
                'UPDATE feeds SET active = 1, fetch_failures = 0 WHERE id = ?',
                $id
            );
        } else {
            $this->db->simple('UPDATE feeds SET active = 0 WHERE id = ?', $id);
        }
    }

    public function countFiltered(?string $search, ?int $active): int
    {
        [$where, $params] = $this->buildFilter($search, $active);
        return (int) $this->db->firstColumn("SELECT COUNT(*) FROM feeds $where", ...$params);
    }

    public function findFiltered(?string $search, ?int $active, int $offset, int $limit): array
    {
        [$where, $params] = $this->buildFilter($search, $active);
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->all(
            "SELECT f.id, f.title, f.feed_url, f.url, f.last_fetch, f.fetch_failures, f.active,
                (SELECT COUNT(*) FROM subscriptions s WHERE s.feed = f.id AND s.deleted = 0) AS subscribers
             FROM feeds f
             $where
             ORDER BY f.id DESC
             LIMIT ? OFFSET ?",
            ...$params
        );
    }

    private function buildFilter(?string $search, ?int $active): array
    {
        $conditions = [];
        $params     = [];

        if ($search !== null && $search !== '') {
            $conditions[] = '(title LIKE ? OR feed_url LIKE ? OR url LIKE ?)';
            $like         = '%' . $search . '%';
            $params[]     = $like;
            $params[]     = $like;
            $params[]     = $like;
        }

        if ($active !== null) {
            $conditions[] = 'active = ?';
            $params[]     = $active;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }

    public function recordFailure(string $url, int $maxFailures = 3): void
    {
        $url = Url::normalize($url);

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
