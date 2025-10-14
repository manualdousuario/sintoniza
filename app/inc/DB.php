<?php

class Database
{
    protected static ?Database $instance = null;
    protected array $statements = [];
    protected MeekroDB $mdb;
    
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

    public function __construct(string $host, string $dbname, string $user, string $password, $port = 3306)
    {
        // Validate connection parameters
        $this->validateString($host, 'Host');
        $this->validateString($dbname, 'Database name');
        $this->validateString($user, 'Username');
        $this->validateString($password, 'Password');
        $this->validateString($port, 'Port');
        
        // Create MeekroDB instance with connection parameters
        $this->mdb = new MeekroDB($host, $user, $password, $dbname, (int)$port, 'utf8mb4');
        
        try {
            // Test connection and set timezone
            $this->mdb->query('SET time_zone = "+00:00"');
            
            // Try to use the database
            try {
                $this->mdb->query('USE `' . $this->sanitizeIdentifier($dbname) . '`');
            }
            catch (Exception $e) {
                // Database doesn't exist, create it
                $this->createDatabase($dbname);
            }

            if (!$this->checkTablesExist()) {
                $this->installSchema();
            }
        }
        catch (Exception $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
        
        self::$instance = $this;
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): ?Database
    {
        return self::$instance;
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
        $this->mdb->query('CREATE DATABASE IF NOT EXISTS `%l`
            DEFAULT CHARACTER SET utf8mb4
            DEFAULT COLLATE utf8mb4_general_ci', $dbname);
        $this->mdb->query('USE `%l`', $dbname);
    }

    protected function checkTablesExist(): bool
    {
        $requiredTables = ['users', 'devices', 'episodes', 'episodes_actions', 'feeds', 'subscriptions'];
        $existingTables = $this->mdb->queryFirstColumn('SHOW TABLES');
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

        $this->mdb->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($commands as $command) {
            if (empty($command)) continue;
            
            if (preg_match('/^(\/\*|SET|--)/i', trim($command))) {
                continue;
            }

            try {
                $this->mdb->query($command);
            }
            catch (Exception $e) {
                $this->mdb->query('SET FOREIGN_KEY_CHECKS = 1');
                throw new RuntimeException(
                    sprintf("Error: %s\n - %s",
                        $e->getMessage(),
                        $command
                    )
                );
            }
        }

        $this->mdb->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Enhanced upsert with parameter validation
     * @throws InvalidArgumentException
     */
    public function upsert(string $table, array $params, array $conflict_columns): void
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

        // Use MeekroDB's insertUpdate method
        $this->mdb->insertUpdate($table, $params);
    }

    /**
     * Execute a raw SQL query (backwards compatibility with PDO)
     */
    public function query(string $sql): object
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $result = $this->mdb->query($sql);
        
        // Return a wrapper object that mimics PDOStatement for compatibility
        return new class($result) {
            private $result;
            
            public function __construct($result) {
                $this->result = $result;
            }
            
            public function fetchAll(?int $mode = null): array {
                return is_array($this->result) ? $this->result : [];
            }
            
            public function fetch(?int $mode = null) {
                if (is_array($this->result) && count($this->result) > 0) {
                    return (object)array_shift($this->result);
                }
                return false;
            }
            
            public function fetchColumn(int $column = 0) {
                if (is_array($this->result) && count($this->result) > 0) {
                    $row = array_shift($this->result);
                    return is_array($row) ? reset($row) : $row;
                }
                return false;
            }
        };
    }

    /**
     * Execute a command (backwards compatibility)
     */
    public function exec(string $sql): int
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $this->mdb->query($sql);
        return $this->mdb->affectedRows();
    }

    /**
     * Prepare statement (for backwards compatibility, but we'll execute immediately)
     */
    public function prepare(string $sql): object
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        $mdb = $this->mdb;
        
        return new class($sql, $mdb) {
            private $sql;
            private $params = [];
            private $mdb;
            
            public function __construct(string $sql, $mdb) {
                $this->sql = $sql;
                $this->mdb = $mdb;
            }
            
            public function bindValue($param, $value, $type = null): void {
                $this->params[$param] = $value;
            }
            
            public function execute(?array $params = null): bool {
                $executeParams = $params ?? $this->params;
                
                // Convert named parameters to positional if needed
                $sql = $this->sql;
                foreach ($executeParams as $key => $value) {
                    if (is_string($key)) {
                        $sql = str_replace($key, '%s', $sql);
                    }
                }
                
                $this->mdb->query($sql, ...array_values($executeParams));
                return true;
            }
            
            public function fetch() {
                return false;
            }
        };
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        $this->mdb->startTransaction();
        return true;
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        $this->mdb->commit();
        return true;
    }

    /**
     * Rollback transaction
     */
    public function rollBack(): bool
    {
        $this->mdb->rollback();
        return true;
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId(): string
    {
        return (string)$this->mdb->insertId();
    }

    /**
     * Enhanced simple query with validation
     * @throws InvalidArgumentException
     */
    public function simple(string $sql, ...$params): void
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        
        // Convert ? placeholders to %s for MeekroDB
        $sql = str_replace('?', '%s', $sql);
        
        // Handle both positional and named parameters
        if (count($params) === 1 && is_array($params[0])) {
            $paramArray = $params[0];
            // Check if it's an associative array (named parameters)
            if (array_keys($paramArray) !== range(0, count($paramArray) - 1)) {
                // Named parameters - convert SQL and parameters
                foreach ($paramArray as $key => $value) {
                    $sql = str_replace(':' . $key, '%s', $sql);
                }
                $params = array_values($paramArray);
            } else {
                $params = $paramArray;
            }
        }
        
        $this->mdb->query($sql, ...$params);
    }

    /**
     * Enhanced firstRow with validation
     * @throws InvalidArgumentException
     */
    public function firstRow(string $sql, ...$params): ?stdClass
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        
        // Convert ? placeholders to %s for MeekroDB
        $sql = str_replace('?', '%s', $sql);
        
        // Handle both positional and named parameters
        if (count($params) === 1 && is_array($params[0])) {
            $paramArray = $params[0];
            // Check if it's an associative array (named parameters)
            if (array_keys($paramArray) !== range(0, count($paramArray) - 1)) {
                // Named parameters - convert SQL and parameters
                foreach ($paramArray as $key => $value) {
                    $sql = str_replace(':' . $key, '%s', $sql);
                }
                $params = array_values($paramArray);
            } else {
                $params = $paramArray;
            }
        }
        
        $row = $this->mdb->queryFirstRow($sql, ...$params);
        return $row ? (object)$row : null;
    }

    /**
     * Enhanced firstColumn with validation
     * @throws InvalidArgumentException
     */
    public function firstColumn(string $sql, ...$params)
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        // Convert ? placeholders to %s for MeekroDB
        $sql = str_replace('?', '%s', $sql);
        $result = $this->mdb->queryFirstField($sql, ...$params);
        return $result !== null ? $result : null;
    }

    /**
     * Enhanced rowsFirstColumn with validation
     * @throws InvalidArgumentException
     */
    public function rowsFirstColumn(string $sql, ...$params): array
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        // Convert ? placeholders to %s for MeekroDB
        $sql = str_replace('?', '%s', $sql);
        return $this->mdb->queryFirstColumn($sql, ...$params);
    }

    /**
     * Enhanced iterate with validation
     * @throws InvalidArgumentException
     */
    public function iterate(string $sql, ...$params): Generator
    {
        $this->validateString($sql, 'SQL query', 'sql_query');
        // Convert ? placeholders to %s for MeekroDB
        $sql = str_replace('?', '%s', $sql);
        $results = $this->mdb->query($sql, ...$params);
        
        foreach ($results as $row) {
            yield (object)$row;
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
