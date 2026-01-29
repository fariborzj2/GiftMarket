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

            CREATE TABLE IF NOT EXISTS product_packs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                pack_size INT NOT NULL DEFAULT 1,
                price_digital DECIMAL(10, 2) NOT NULL DEFAULT 0,
                price_physical DECIMAL(10, 2) NOT NULL DEFAULT 0,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_name VARCHAR(100) UNIQUE NOT NULL,
                key_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS telegram_channels (
                id INT AUTO_INCREMENT PRIMARY KEY,
                channel_id VARCHAR(100) NOT NULL UNIQUE,
                name VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS telegram_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                brand_code VARCHAR(50) NOT NULL,
                country_code VARCHAR(50) NOT NULL,
                enabled TINYINT(1) DEFAULT 1,
                UNIQUE KEY brand_country (brand_code, country_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS telegram_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                status VARCHAR(20) NOT NULL,
                message TEXT,
                response TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

        // Migration to many-packs per product
        try {
            // Check if product_packs is empty and products has data
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM product_packs");
            $packCount = $stmt->fetchColumn();

            $stmt = $this->pdo->query("SELECT COUNT(*) FROM products");
            $productCount = $stmt->fetchColumn();

            if ($packCount == 0 && $productCount > 0) {
                // Initial migration: move current product data to packs
                $this->pdo->exec("INSERT INTO product_packs (product_id, pack_size, price_digital, price_physical)
                                  SELECT id, pack_size, price_digital, price_physical FROM products");

                // Identify duplicates and merge them
                $stmt = $this->pdo->query("
                    SELECT MIN(id) as master_id, brand, denomination, country, currency
                    FROM products
                    GROUP BY brand, denomination, country, currency
                    HAVING COUNT(*) > 1
                ");
                $duplicates = $stmt->fetchAll();

                foreach ($duplicates as $dup) {
                    $masterId = $dup['master_id'];
                    // Get all other IDs for this product identity
                    $stmt2 = $this->pdo->prepare("
                        SELECT id FROM products
                        WHERE brand = ? AND denomination = ? AND country = ? AND currency = ? AND id != ?
                    ");
                    $stmt2->execute([$dup['brand'], $dup['denomination'], $dup['country'], $dup['currency'], $masterId]);
                    $otherIds = $stmt2->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($otherIds)) {
                        $placeholders = implode(',', array_fill(0, count($otherIds), '?'));
                        // Update product_packs to point to master_id
                        $stmt3 = $this->pdo->prepare("UPDATE product_packs SET product_id = ? WHERE product_id IN ($placeholders)");
                        $stmt3->execute(array_merge([$masterId], $otherIds));

                        // Delete other product records
                        $stmt4 = $this->pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                        $stmt4->execute($otherIds);
                    }
                }
            }
        } catch (PDOException $e) {
            // Log or handle error
        }

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

        // Add default settings
        try {
            $defaultSettings = [
                'usd_to_aed' => '3.673',
                'auto_update_rate' => '0',
                'update_interval_hours' => '12',
                'last_rate_update' => '0'
            ];

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM settings WHERE key_name = ?");
            $insertStmt = $this->pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?)");

            $telegramSettings = [
                'telegram_bot_enabled' => '0',
                'telegram_bot_token' => '',
                'telegram_bot_username' => '',
                'telegram_publish_time' => '09:00',
                'telegram_last_publish_date' => '',
                'telegram_message_template' => "*{brand}* {country} ({denomination})\n{type}: {price}{currency} → {converted_price} {target_currency}\nLast update: {last_update}",
                'telegram_use_emojis' => '1',
                'telegram_price_type' => 'both'
            ];

            foreach ($telegramSettings as $key => $value) {
                $stmt->execute([$key]);
                if ($stmt->fetchColumn() == 0) {
                    $insertStmt->execute([$key, $value]);
                }
            }

            foreach ($defaultSettings as $key => $value) {
                $stmt->execute([$key]);
                if ($stmt->fetchColumn() == 0) {
                    $insertStmt->execute([$key, $value]);
                }
            }
        } catch (PDOException $e) {
            // Ignore if insert fails
        }
    }
}
