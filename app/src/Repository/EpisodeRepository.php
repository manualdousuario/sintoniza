<?php

declare(strict_types=1);

namespace Sintoniza\Repository;

use Sintoniza\Database\DB;
use stdClass;

class EpisodeRepository
{
    public function __construct(private DB $db) {}

    public function findById(int $id): ?stdClass
    {
        return $this->db->firstRow('SELECT * FROM episodes WHERE id = ?', $id);
    }

    public function findByUrl(string $url, int $feedId): ?stdClass
    {
        return $this->db->firstRow(
            'SELECT * FROM episodes WHERE url = ? AND feed_id = ?',
            $url,
            $feedId
        );
    }

    public function findByFeed(int $feedId, int $limit = 50): array
    {
        return $this->db->all(
            'SELECT * FROM episodes WHERE feed_id = ? ORDER BY pubdate DESC LIMIT ?',
            $feedId,
            $limit
        );
    }

    public function findRecentByUser(int $userId, int $limit = 20): array
    {
        return $this->db->all(
            'SELECT e.*, ea.action, ea.position, ea.timestamp
             FROM episodes e
             INNER JOIN episodes_actions ea ON ea.episode_id = e.id
             WHERE ea.user_id = ?
             ORDER BY ea.timestamp DESC
             LIMIT ?',
            $userId,
            $limit
        );
    }

    public function upsert(array $data): void
    {
        $this->db->upsert('episodes', $data, ['url', 'feed_id']);
    }

    public function countByFeed(int $feedId): int
    {
        return (int) $this->db->firstColumn(
            'SELECT COUNT(*) FROM episodes WHERE feed_id = ?',
            $feedId
        );
    }

    public function count(): int
    {
        return (int) $this->db->firstColumn('SELECT COUNT(*) FROM episodes');
    }
}
