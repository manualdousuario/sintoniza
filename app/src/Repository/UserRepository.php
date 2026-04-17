<?php

declare(strict_types=1);

namespace Sintoniza\Repository;

use Sintoniza\Database\DB;
use stdClass;

class UserRepository
{
    public function __construct(private DB $db) {}

    public function findById(int $id): ?stdClass
    {
        return $this->db->firstRow('SELECT * FROM users WHERE id = ?', $id);
    }

    public function findByUsername(string $username): ?stdClass
    {
        return $this->db->firstRow('SELECT * FROM users WHERE name = ?', $username);
    }

    public function findByEmail(string $email): ?stdClass
    {
        return $this->db->firstRow('SELECT * FROM users WHERE email = ?', $email);
    }

    public function findAll(): array
    {
        return $this->db->all('SELECT * FROM users ORDER BY id ASC');
    }

    public function create(string $username, string $passwordHash, string $email, bool $admin = false): int
    {
        $this->db->simple(
            'INSERT INTO users (name, password, email, admin) VALUES (?, ?, ?, ?)',
            $username,
            $passwordHash,
            $email,
            (int) $admin
        );

        return (int) $this->db->lastInsertId();
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->db->simple('UPDATE users SET password = ? WHERE id = ?', $passwordHash, $id);
    }

    public function updateTimezone(int $id, string $timezone): void
    {
        $this->db->simple('UPDATE users SET timezone = ? WHERE id = ?', $timezone, $id);
    }

    public function delete(int $id): void
    {
        $this->db->simple('DELETE FROM users WHERE id = ?', $id);
    }

    public function count(): int
    {
        return (int) $this->db->firstColumn('SELECT COUNT(*) FROM users');
    }

    public function findPaginated(int $offset, int $limit): array
    {
        return $this->db->all(
            'SELECT id, name, email, admin, active FROM users ORDER BY id DESC LIMIT ? OFFSET ?',
            $limit,
            $offset
        );
    }

    public function countFiltered(?string $search, ?int $active): int
    {
        [$where, $params] = $this->buildFilter($search, $active);
        return (int) $this->db->firstColumn("SELECT COUNT(*) FROM users $where", ...$params);
    }

    public function findFiltered(?string $search, ?int $active, int $offset, int $limit): array
    {
        [$where, $params] = $this->buildFilter($search, $active);
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->all(
            "SELECT id, name, email, admin, active FROM users $where ORDER BY id DESC LIMIT ? OFFSET ?",
            ...$params
        );
    }

    private function buildFilter(?string $search, ?int $active): array
    {
        $conditions = [];
        $params     = [];

        if ($search !== null && $search !== '') {
            $conditions[] = '(name LIKE ? OR email LIKE ?)';
            $like         = '%' . $search . '%';
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

    public function updateInfo(int $id, string $email, bool $admin): void
    {
        $this->db->simple('UPDATE users SET email = ?, admin = ? WHERE id = ?', $email, (int) $admin, $id);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->db->simple('UPDATE users SET active = ? WHERE id = ?', (int) $active, $id);
    }

    public function updatePasswordResetToken(int $id, string $token, int $expiresAt): void
    {
        $this->db->simple(
            'UPDATE users SET password_reset_token = ?, password_reset_token_expires_at = ? WHERE id = ?',
            $token,
            $expiresAt,
            $id
        );
    }

    public function findByResetToken(string $token): ?stdClass
    {
        return $this->db->firstRow(
            'SELECT * FROM users WHERE password_reset_token = ? AND password_reset_token_expires_at > ?',
            $token,
            time()
        );
    }

    public function clearResetToken(int $id): void
    {
        $this->db->simple(
            'UPDATE users SET password_reset_token = NULL, password_reset_token_expires_at = NULL WHERE id = ?',
            $id
        );
    }
}
