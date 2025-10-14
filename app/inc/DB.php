<?php

class DB extends PDO
{
    protected array $statements = [];
    
    // Define allowed types for validation
    protected const VALID_TYPES = [
        'string' => 'is_string',
        'int' => 'is_int',
        'float' => 'is_float',
        'bool' => 'is_bool',
        'array' => 'is_array',
        'null' => 'is_null'
    ];

    // Common regex patterns for validation
    protected const PATTERNS = [
        'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'url' => '/^https?:\/\/[^\s\/$.?#].[^\s]*$/',
        'date' => '/^\d{4}-\d{2}-\d{2}$/',
        'datetime' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
        'alphanumeric' => '/^[a-zA-Z0-9]+$/',
        'numeric' => '/^[0-9]+$/'
    ];

    // Define max lengths for different types of strings
    protected const MAX_LENGTHS = [
        'sql_query' => 10000,    // Allow longer SQL queries
        'identifier' => 64,      // Database identifiers (table names, column names)
        'default' => 255         // Default max length for other strings
    ];

    public function __construct(string $host, string $dbname, string $user, string $password, ?string $port = null)
    {
        // Validate connection parameters
        $this->validateString($host, 'Host');
        $this->validateString($dbname, 'Database name');
        $this->validateString($user, 'Username');
        $this->validateString($password, 'Password');
        
        // Validate port if provided
        if ($port !== null && $port !== '') {
            $this->validateNumeric($port, 'Port', 1, 65535);
        }

        // Build DSN with optional port
        $dsn = sprintf('mysql:host=%s;charset=utf8mb4', $this->sanitizeIdentifier($host));
        if ($port !== null && $port !== '') {
            $dsn .= sprintf(';port=%d', (int)$port);
        }
        
        parent::__construct($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ]);

        $this->exec('SET time_zone = "+00:00"');
        
        try {
            $this->exec(sprintf('USE `%s`', $this->sanitizeIdentifier($dbname)));
        }
        catch (PDOException $e) {
            if ($e->getCode() == 1049) {
                $this->createDatabase($dbname);
            } else {
                throw $e;
            }
        }

        if (!$this->checkTablesExist()) {
            $this->installSchema();
        }
    }

    /**
     * Validates a parameter against a specific type
     * @throws InvalidArgumentException
     */
    protected function validateType($value, string $type, string $paramName): void 
    {
        if (!isset(self::VALID_TYPES[$type])) {
            throw new InvalidArgumentException("Invalid type specified for parameter '$paramName'");
        }

        $validationFunction = self::VALID_TYPES[$type];
        if (!$validationFunction($value)) {
            throw new InvalidArgumentException("Parameter '$paramName' must be of type $type");
        }
    }

    /**
     * Validates string parameters with context-aware max lengths
     * @throws InvalidArgumentException
     */
    protected function validateString($value, string $paramName, ?string $context = null): void 
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("Parameter '$paramName' must be a string");
        }

        // Determine max length based on context
        $maxLength = self::MAX_LENGTHS[$context ?? 'default'] ?? self::MAX_LENGTHS['default'];

        if (strlen($value) > $maxLength) {
            throw new InvalidArgumentException("Parameter '$paramName' exceeds maximum length of $maxLength");
        }
    }

    /**
     * Validates numeric parameters
     * @throws InvalidArgumentException
     */
    protected function validateNumeric($value, string $paramName, $min = null, $max = null): void 
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Parameter '$paramName' must be numeric");
        }

        if ($min !== null && $value < $min) {
            throw new InvalidArgumentException("Parameter '$paramName' must be greater than or equal to $min");
        }

        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("Parameter '$paramName' must be less than or equal to $max");
        }
    }

    /**
     * Sanitizes database identifiers (table names, column names)
     */
    protected function sanitizeIdentifier(string $identifier): string 
    {
        // Validate identifier length
        $this->validateString($identifier, 'Database identifier', 'identifier');
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }

    /**
     * Validates parameters against specific patterns
     * @throws InvalidArgumentException
     */
    protected function validatePattern($value, string $pattern, string $paramName): void 
    {
        if (!isset(self::PATTERNS[$pattern])) {
            throw new InvalidArgumentException("Invalid pattern specified");
        }

        if (!preg_match(self::PATTERNS[$pattern], $value)) {
            throw new InvalidArgumentException("Parameter '$paramName' does not match required pattern");
        }
    }

    protected function createDatabase(string $dbname): void
    {
        $dbname = $this->sanitizeIdentifier($dbname);
        $this->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` 
            DEFAULT CHARACTER SET utf8mb4 
            DEFAULT COLLATE utf8mb4_general_ci', $dbname));
        $this->exec(sprintf('USE `%s`', $dbname));
    }

    protected function checkTablesExist(): bool
    {
        $requiredTables = ['users', 'devices', 'episodes', 'episodes_actions', 'feeds', 'subscriptions'];
        $existingTables = $this->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        return count(array_intersect($requiredTables, $existingTables)) === count($requiredTables);
    }

    protected function installSchema(): void
    {
        $sqlFile = __DIR__ . '/mysql.sql';
        
        if (!file_exists($sqlFile)) {
            throw new RuntimeException(__('db.schema_not_found'));
        }

        $sql = file_get_contents($sqlFile);
        
        $commands = array_filter(
            array_map(
                'trim',
                preg_split("/;[\r\n]+/", $sql)
            )
        );

        $this->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($commands as $command) {
            if (empty($command)) continue;
            
            if (preg_match('/^(\/\*|SET|--)/i', trim($command))) {
                continue;
            }

            try {
                $this->exec($command);
            }
            catch (PDOException $e) {
                $this->exec('SET FOREIGN_KEY_CHECKS = 1');
                throw new RuntimeException(
                    sprintf("Error: %s\n - %s", 
                        $e->getMessage(), 
                        $command
                    )
                );
            }
        }

        $this->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Enhanced upsert with parameter validation
     * @throws InvalidArgumentException
     */
    public function upsert(string $table, array $params, array $conflict_columns): ?PDOStatement
    {
        // Validate table name
        $this->validateString($table, 'Table name', 'identifier');
        $table = $this->sanitizeIdentifier($table);

        // Validate parameters
        if (empty($params)) {
            throw new InvalidArgumentException("Parameters array cannot be empty");
        }

        // Validate conflict columns
        if (empty($conflict_columns)) {
            throw new InvalidArgumentException("Conflict columns array cannot be empty");
        }

        foreach ($conflict_columns as $column) {
            $this->validateString($column, 'Conflict column', 'identifier');
        }

        $columns = array_keys($params);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        $updates = array_map(fn($col) => "$col = VALUES($col)", $columns);

        // Sanitize column names
        $columns = array_map([$this, 'sanitizeIdentifier'], $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders),
            implode(', ', $updates)
        );

        return $this->simple($sql, $params);
    }

    /**
     * Enhanced prepare with parameter validation
     * @throws InvalidArgumentException
     */
    public function prepare2(string $sql, ...$params): PDOStatement
    {
        // Validate SQL query with higher length limit
        $this->validateString($sql, 'SQL query', 'sql_query');

        $hash = md5($sql);

        if (!array_key_exists($hash, $this->statements)) {
            $st = $this->statements[$hash] = $this->prepare($sql);
        }
        else {
            $st = $this->statements[$hash];
        }

        if (isset($params[0]) && is_array($params[0])) {
            $params = $params[0];
        }

        foreach ($params as $key => $value) {
            // Determine parameter type for proper binding
            $type = PDO::PARAM_STR;
            if (is_int($value)) $type = PDO::PARAM_INT;
            elseif (is_bool($value)) $type = PDO::PARAM_BOOL;
            elseif (is_null($value)) $type = PDO::PARAM_NULL;

            if (is_int($key)) {
                $st->bindValue($key + 1, $value, $type);
            }
            else {
                $st->bindValue(':' . $key, $value, $type);
            }
        }

        return $st;
    }

    /**
     * Enhanced simple query with validation
     * @throws InvalidArgumentException
     */
    public function simple(string $sql, ...$params): ?PDOStatement
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $st = $this->prepare2($sql, ...$params);
        $st->execute();
        return $st;
    }

    /**
     * Enhanced firstRow with validation
     * @throws InvalidArgumentException
     */
    public function firstRow(string $sql, ...$params): ?stdClass
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $st = $this->simple($sql, ...$params);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Enhanced firstColumn with validation
     * @throws InvalidArgumentException
     */
    public function firstColumn(string $sql, ...$params)
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $st = $this->simple($sql, ...$params);
        return $st->fetchColumn() ?: null;
    }

    /**
     * Enhanced rowsFirstColumn with validation
     * @throws InvalidArgumentException
     */
    public function rowsFirstColumn(string $sql, ...$params): array
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $st = $this->simple($sql, ...$params);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Enhanced iterate with validation
     * @throws InvalidArgumentException
     */
    public function iterate(string $sql, ...$params): Generator
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $st = $this->simple($sql, ...$params);
        
        while ($row = $st->fetch()) {
            yield $row;
        }
    }

    /**
     * Enhanced all with validation
     * @throws InvalidArgumentException
     */
    public function all(string $sql, ...$params): array
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        return iterator_to_array($this->iterate($sql, ...$params));
    }
}
