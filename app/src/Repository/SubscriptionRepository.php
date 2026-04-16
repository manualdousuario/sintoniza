<?php

declare(strict_types=1);

namespace Sintoniza\Repository;

use Sintoniza\Database\DB;
use stdClass;

class SubscriptionRepository
{
    public function __construct(private DB $db) {}

    public function findByUser(int $userId): array
    {
        return $this->db->all(
            'SELECT f.url, f.title, f.image_url, f.description, s.created_at
             FROM subscriptions s
             INNER JOIN feeds f ON f.id = s.feed_id
             WHERE s.user_id = ?
             ORDER BY f.title ASC',
            $userId
        );
    }

    public function exists(int $userId, int $feedId): bool
    {
        return (bool) $this->db->firstColumn(
            'SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND feed_id = ?',
            $userId,
            $feedId
        );
    }

    public function add(int $userId, int $feedId): void
    {
        $this->db->simple(
            'INSERT IGNORE INTO subscriptions (user_id, feed_id, created_at) VALUES (?, ?, NOW())',
            $userId,
            $feedId
        );
    }

    public function remove(int $userId, int $feedId): void
    {
        $this->db->simple(
            'DELETE FROM subscriptions WHERE user_id = ? AND feed_id = ?',
            $userId,
            $feedId
        );
    }

    public function countByUser(int $userId): int
    {
        return (int) $this->db->firstColumn(
            'SELECT COUNT(*) FROM subscriptions WHERE user_id = ?',
            $userId
        );
    }

    public function count(): int
    {
        return (int) $this->db->firstColumn('SELECT COUNT(*) FROM subscriptions');
    }
}
