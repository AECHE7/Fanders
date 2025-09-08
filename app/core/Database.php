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
    private $pdo;
    private $error;
    private static $instance = null;

    /**
     * Constructor - establishes database connection
     */
    private function __construct() {
        $dsn = "{$this->dbType}:host={$this->host};dbname={$this->dbName}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Connection Error: {$this->error}");
            die("Database Connection Error: {$this->error}");
        }
    }

    /**
     * Singleton pattern implementation to ensure only one database connection
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO instance
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Execute a query
     * 
     * @param string $sql
     * @param array $params
     * @return PDOStatement|bool
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
     * Get a single record
     * 
     * @param string $sql
     * @param array $params
     * @return array|bool
     */
    public function single($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }

    /**
     * Get multiple records
     * 
     * @param string $sql
     * @param array $params
     * @return array|bool
     */
    public function resultSet($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return false;
    }

    /**
     * Get row count from last query
     * 
     * @param string $sql
     * @param array $params
     * @return int
     */
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return 0;
    }

    /**
     * Get last inserted ID
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * Get error message
     * 
     * @return string
     */
    public function getError() {
        return $this->error;
    }
}
