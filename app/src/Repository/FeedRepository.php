<?php

declare(strict_types=1);

namespace Sintoniza\Repository;

use Sintoniza\Database\DB;
use stdClass;

class FeedRepository
{
    public function __construct(private DB $db) {}

    public function findById(int $id): ?stdClass
    {
        return $this->db->firstRow('SELECT * FROM feeds WHERE id = ?', $id);
    }

    public function findByUrl(string $url): ?stdClass
    {
        return $this->db->firstRow('SELECT * FROM feeds WHERE url = ?', $url);
    }

    public function findAllByUser(int $userId): array
    {
        return $this->db->all(
            'SELECT f.* FROM feeds f
             INNER JOIN subscriptions s ON s.feed_id = f.id
             WHERE s.user_id = ?
             ORDER BY f.title ASC',
            $userId
        );
    }

    public function findAll(): array
    {
        return $this->db->all('SELECT * FROM feeds ORDER BY title ASC');
    }

    public function countSubscribers(int $feedId): int
    {
        return (int) $this->db->firstColumn(
            'SELECT COUNT(*) FROM subscriptions WHERE feed_id = ?',
            $feedId
        );
    }

    public function upsert(array $data): void
    {
        $this->db->upsert('feeds', $data, ['url']);
    }

    public function updateLastFetch(int $id): void
    {
        $this->db->simple('UPDATE feeds SET last_fetch = NOW() WHERE id = ?', $id);
    }

    public function count(): int
    {
        return (int) $this->db->firstColumn('SELECT COUNT(*) FROM feeds');
    }

    public function findStale(int $minutes = 60): array
    {
        return $this->db->all(
            'SELECT * FROM feeds WHERE last_fetch < DATE_SUB(NOW(), INTERVAL ? MINUTE) OR last_fetch IS NULL',
            $minutes
        );
    }
}
