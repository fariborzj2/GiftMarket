<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initialize();
        } catch (PDOException $e) {
            // In production, you might want to log this instead of dying
            die("Database connection failed: " . $e->getMessage());
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

    private function initialize() {
        // Create initial tables if they don't exist
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) DEFAULT 'admin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                brand VARCHAR(100) NOT NULL,
                denomination VARCHAR(100) NOT NULL,
                country VARCHAR(50) NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                stock INT DEFAULT 0,
                type VARCHAR(20) DEFAULT 'digital',
                status INT DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS countries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(10) UNIQUE NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        // Multi-query handling for initialization
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // Tables might already exist or user might not have CREATE permissions
        }

        // Add default admin if not exists (password: admin123)
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                $password = password_hash('admin123', PASSWORD_DEFAULT);
                $this->pdo->exec("INSERT INTO users (username, password) VALUES ('admin', '$password')");
            }
        } catch (PDOException $e) {
            // Ignore if insert fails
        }
    }
}
