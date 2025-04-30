<?php
require_once 'config.php';

/**
 * Database Connection Class
 */
class Database {
    private $conn;
    private static $instance;

    /**
     * Connect to the database
     */
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            $this->conn->set_charset("utf8");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Execute query
     */
    public function query($sql) {
        return $this->conn->query($sql);
    }

    /**
     * Prepare statement
     */
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    /**
     * Escape string
     */
    public function escapeString($value) {
        return $this->conn->real_escape_string($value);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->conn->autocommit(FALSE);
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->conn->commit();
        $this->conn->autocommit(TRUE);
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->conn->rollback();
        $this->conn->autocommit(TRUE);
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn->close();
    }
}
