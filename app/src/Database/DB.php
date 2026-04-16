<?php

declare(strict_types=1);

namespace Sintoniza\Database;

use Generator;
use InvalidArgumentException;
use stdClass;

class DB
{
    private \MeekroDB $mdb;

    protected const VALID_TYPES = [
        'string' => 'is_string',
        'int'    => 'is_int',
        'float'  => 'is_float',
        'bool'   => 'is_bool',
        'array'  => 'is_array',
        'null'   => 'is_null',
    ];

    protected const PATTERNS = [
        'email'        => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'url'          => '/^https?:\/\/[^\s\/$.?#].[^\s]*$/',
        'date'         => '/^\d{4}-\d{2}-\d{2}$/',
        'datetime'     => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
        'alphanumeric' => '/^[a-zA-Z0-9]+$/',
        'numeric'      => '/^[0-9]+$/',
    ];

    protected const MAX_LENGTHS = [
        'sql_query'  => 10000,
        'identifier' => 64,
        'default'    => 255,
    ];

    public function __construct(string $host, string $dbname, string $user, string $password, mixed $port = 3306)
    {
        $this->validateString($host, 'Host');
        $this->validateString($dbname, 'Database name');
        $this->validateString($user, 'Username');
        $this->validateString($password, 'Password');
        $this->validateString((string) $port, 'Port');

        $dsn       = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
        $this->mdb = new \MeekroDB($dsn, $user, $password);
        $this->mdb->get()->exec('SET time_zone = "+00:00"');

        $safeName = $this->sanitizeIdentifier($dbname);
        $this->mdb->get()->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_general_ci',
            $safeName
        ));
        $this->mdb->useDB($safeName);
    }

    protected function validateType(mixed $value, string $type, string $paramName): void
    {
        if (!isset(self::VALID_TYPES[$type])) {
            throw new InvalidArgumentException("Invalid type specified for parameter '$paramName'");
        }

        $fn = self::VALID_TYPES[$type];
        if (!$fn($value)) {
            throw new InvalidArgumentException("Parameter '$paramName' must be of type $type");
        }
    }

    protected function validateString(mixed $value, string $paramName, ?string $context = null): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("Parameter '$paramName' must be a string");
        }

        $maxLength = self::MAX_LENGTHS[$context ?? 'default'] ?? self::MAX_LENGTHS['default'];

        if (strlen($value) > $maxLength) {
            throw new InvalidArgumentException("Parameter '$paramName' exceeds maximum length of $maxLength");
        }
    }

    protected function validateNumeric(mixed $value, string $paramName, mixed $min = null, mixed $max = null): void
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Parameter '$paramName' must be numeric");
        }

        if ($min !== null && $value < $min) {
            throw new InvalidArgumentException("Parameter '$paramName' must be >= $min");
        }

        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("Parameter '$paramName' must be <= $max");
        }
    }

    protected function sanitizeIdentifier(string $identifier): string
    {
        $this->validateString($identifier, 'Database identifier', 'identifier');
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }

    protected function validatePattern(mixed $value, string $pattern, string $paramName): void
    {
        if (!isset(self::PATTERNS[$pattern])) {
            throw new InvalidArgumentException("Invalid pattern specified");
        }

        if (!preg_match(self::PATTERNS[$pattern], $value)) {
            throw new InvalidArgumentException("Parameter '$paramName' does not match required pattern");
        }
    }

    private function toMeekroSql(string $sql): string
    {
        return str_replace('?', '%?', str_replace('%', '%%', $sql));
    }

    private function flattenParams(array $params): array
    {
        return (isset($params[0]) && is_array($params[0])) ? $params[0] : $params;
    }

    public function exec(string $sql): int|false
    {
        return $this->mdb->get()->exec($sql);
    }

    public function beginTransaction(): void
    {
        $this->mdb->startTransaction();
    }

    public function commit(): void
    {
        $this->mdb->commit();
    }

    public function rollBack(): void
    {
        $this->mdb->rollback();
    }

    public function lastInsertId(): string
    {
        return (string) $this->mdb->insertId();
    }

    public function upsert(string $table, array $params, array $conflict_columns): void
    {
        $this->validateString($table, 'Table name', 'identifier');
        $table = $this->sanitizeIdentifier($table);

        if (empty($params)) {
            throw new InvalidArgumentException("Parameters array cannot be empty");
        }

        if (empty($conflict_columns)) {
            throw new InvalidArgumentException("Conflict columns array cannot be empty");
        }

        foreach ($conflict_columns as $column) {
            $this->validateString($column, 'Conflict column', 'identifier');
        }

        $this->mdb->insertUpdate($table, $params);
    }

    public function simple(string $sql, mixed ...$params): void
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $params = $this->flattenParams($params);
        $this->mdb->query($this->toMeekroSql($sql), ...$params);
    }

    public function firstRow(string $sql, mixed ...$params): ?stdClass
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $params = $this->flattenParams($params);
        $row    = $this->mdb->queryFirstRow($this->toMeekroSql($sql), ...$params);
        return $row ? (object) $row : null;
    }

    public function firstColumn(string $sql, mixed ...$params): mixed
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $params = $this->flattenParams($params);
        return $this->mdb->queryFirstField($this->toMeekroSql($sql), ...$params) ?? null;
    }

    public function rowsFirstColumn(string $sql, mixed ...$params): array
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $params = $this->flattenParams($params);
        return $this->mdb->queryFirstColumn($this->toMeekroSql($sql), ...$params) ?: [];
    }

    public function all(string $sql, mixed ...$params): array
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $params = $this->flattenParams($params);
        $rows   = $this->mdb->query($this->toMeekroSql($sql), ...$params);
        if (!is_array($rows)) {
            return [];
        }
        return array_map(fn($row) => (object) $row, $rows);
    }

    public function iterate(string $sql, mixed ...$params): Generator
    {
        foreach ($this->all($sql, ...$params) as $row) {
            yield $row;
        }
    }
}
