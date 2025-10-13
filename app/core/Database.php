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
    private $port = DB_PORT; // Add the port
    private $poolMode = DB_POOL_MODE; // Add the pool mode
    private $pdo;
    private $error;
    private static $instance = null;

    private function __construct() {
        try {
            // Build the PDO DSN for PostgreSQL
            $dsn = "{$this->dbType}:host={$this->host};port={$this->port};dbname={$this->dbName}";

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

    public function single($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }

    public function resultSet($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return false;
    }

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
}