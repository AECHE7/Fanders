<?php
/**
 * Database class using PDO for database connection and operations
 */
class Database {
    private $host = DB_HOST;
    private $dbName = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $dbType = DB_TYPE;
    private $port = DB_PORT; 
    private $poolMode = DB_POOL_MODE; 
    private $pdo;
    private $error;
    private static $instance = null;

    private function __construct() {
        try {
            // Build the PDO DSN (e.g., 'pgsql:host=localhost;port=5432;dbname=fanders;connect_timeout=30;sslmode=require')
            $dsn = "{$this->dbType}:host={$this->host};port={$this->port};dbname={$this->dbName};connect_timeout=30;sslmode=require";

            // Set PDO options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Attempt to connect to the database using PDO
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            // Handle the connection error
            die("Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Executes a prepared SQL statement.
     * @param string $sql The SQL query string.
     * @param array $params Optional array of parameters to bind.
     * @return PDOStatement|false The PDO statement object on success, or false on failure.
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Query Error: {$this->error}");
            return false;
        }
    }

    /**
     * Executes a query and fetches a single row.
     */
    public function single($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }

    /**
     * Executes a query and fetches all rows.
     */
    public function resultSet($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return false;
    }

    /**
     * Executes a query and returns the row count.
     */
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return 0;
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollBack();
    }

    public function getError() {
        return $this->error;
    }

    /**
     * Prepares a SQL statement for execution.
     * @param string $sql The SQL query string.
     * @return PDOStatement|false The PDO statement object on success, or false on failure.
     */
    public function prepare($sql) {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Prepare Error: {$this->error}");
            return false;
        }
    }
}
