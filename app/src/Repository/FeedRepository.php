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

    public function findByUrl(string $url): ?\stdClass
    {
        $url = Url::normalizeFeed($url);
        if ($url === '') {
            return null;
        }

        $row = $this->db->firstRow('SELECT * FROM feeds WHERE feed_url = ?', $url);
        if ($row) {
            return $row;
        }

        return $this->db->firstRow(
            'SELECT f.* FROM feeds f
             INNER JOIN feed_aliases a ON a.feed_id = f.id
             WHERE a.url = ?',
            $url
        );
    }

    public function resolveCanonicalUrl(string $url): string
    {
        $url = Url::normalizeFeed($url);
        if ($url === '') {
            return '';
        }

        $canonical = $this->db->firstColumn(
            'SELECT f.feed_url FROM feed_aliases a
             INNER JOIN feeds f ON f.id = a.feed_id
             WHERE a.url = ?',
            $url
        );

        return $canonical ? (string) $canonical : $url;
    }

    public function countFiltered(?string $search): int
    {
        [$where, $params] = $this->buildFilter($search);
        return (int) $this->db->firstColumn("SELECT COUNT(*) FROM feeds $where", ...$params);
    }

    public function findFiltered(?string $search, int $offset, int $limit): array
    {
        [$where, $params] = $this->buildFilter($search);
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->all(
            "SELECT f.id, f.title, f.feed_url, f.url, f.last_fetch,
                (SELECT COUNT(*) FROM subscriptions s WHERE s.feed = f.id AND s.deleted = 0) AS subscribers
             FROM feeds f
             $where
             ORDER BY f.id DESC
             LIMIT ? OFFSET ?",
            ...$params
        );
    }

    private function buildFilter(?string $search): array
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

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }
}
