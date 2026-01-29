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
                price DECIMAL(10, 2) NOT NULL DEFAULT 0,
                price_digital DECIMAL(10, 2) NOT NULL DEFAULT 0,
                price_physical DECIMAL(10, 2) NOT NULL DEFAULT 0,
                currency VARCHAR(10) NOT NULL,
                pack_size INT DEFAULT 1,
                stock INT DEFAULT 0,
                type VARCHAR(20) DEFAULT 'digital',
                status INT DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS countries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(10) UNIQUE NOT NULL,
                flag VARCHAR(255) DEFAULT NULL,
                currency VARCHAR(10) DEFAULT NULL,
                sort_order INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS brands (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(20) UNIQUE NOT NULL,
                logo VARCHAR(255) DEFAULT NULL,
                sort_order INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        // Multi-query handling for initialization
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {}

        // Ensure countries table has all required columns (migration for old installations)
        try {
            $this->pdo->exec("ALTER TABLE countries ADD COLUMN flag VARCHAR(255) DEFAULT NULL");
        } catch (PDOException $e) {}

        try {
            $this->pdo->exec("ALTER TABLE countries ADD COLUMN currency VARCHAR(10) DEFAULT NULL");
        } catch (PDOException $e) {}

        try {
            $this->pdo->exec("ALTER TABLE products ADD COLUMN price_digital DECIMAL(10, 2) NOT NULL DEFAULT 0");
        } catch (PDOException $e) {}

        try {
            $this->pdo->exec("ALTER TABLE products ADD COLUMN price_physical DECIMAL(10, 2) NOT NULL DEFAULT 0");
        } catch (PDOException $e) {}

        try {
            $this->pdo->exec("ALTER TABLE products ADD COLUMN pack_size INT DEFAULT 1");
        } catch (PDOException $e) {}

        try {
            $this->pdo->exec("ALTER TABLE countries ADD COLUMN sort_order INT DEFAULT 0");
        } catch (PDOException $e) {}

        try {
            $this->pdo->exec("ALTER TABLE brands ADD COLUMN sort_order INT DEFAULT 0");
        } catch (PDOException $e) {}

        // Migrate existing price to price_digital and price_physical if they are 0
        try {
            $this->pdo->exec("UPDATE products SET price_digital = price, price_physical = price WHERE price_digital = 0 AND price_physical = 0 AND price > 0");
        } catch (PDOException $e) {}

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
