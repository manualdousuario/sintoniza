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

    public function updatePasswordResetToken(int $id, string $token, string $expiresAt): void
    {
        $this->db->simple(
            'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?',
            $token,
            $expiresAt,
            $id
        );
    }

    public function findByResetToken(string $token): ?stdClass
    {
        return $this->db->firstRow(
            'SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()',
            $token
        );
    }

    public function clearResetToken(int $id): void
    {
        $this->db->simple(
            'UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?',
            $id
        );
    }
}
