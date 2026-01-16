<?php
declare(strict_types=1);

/**
 * Database configuration and connection functions
 */

function getDbConnection(): PDO
{
    static $conn = null;
    
    if ($conn === null) {
        $host = 'sql208.infinityfree.com';
        $dbname = 'if0_39943693_wp37';
        $username = 'if0_39943693';
        $password = 'NOhCh9FCgVc';
        $port = 3306;
        
        try {
            if (!extension_loaded('pdo') || !in_array('mysql', PDO::getAvailableDrivers())) {
                throw new Exception('MySQL PDO driver is not available');
            }
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $conn = new PDO($dsn, $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Ensure necessary tables exist on first connection (safe to call repeatedly)
            if (function_exists('initDatabaseTables')) {
                initDatabaseTables();
            }
            
            return $conn;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $conn;
}

/**
 * Initialize database tables if they don't exist
 */
function initDatabaseTables(): void
{
    try {
        $conn = getDbConnection();
        
        // Create feedback/support table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS Support (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(500),
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create recruit/applications table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS Recruit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                position_id INT NOT NULL,
                motivation TEXT NOT NULL,
                file_path VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_position (position_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create forum posts/comments tables (comments stored separately)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS Posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                author VARCHAR(255) NOT NULL,
                title VARCHAR(500),
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->exec("
            CREATE TABLE IF NOT EXISTS Comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                author VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_post_id (post_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Create Forum table (used to store comments)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS Forum (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                author VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_post_id (post_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Create Orders table to store purchases
        $conn->exec("
            CREATE TABLE IF NOT EXISTS Orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(50) NOT NULL,
                customer_email VARCHAR(255) NOT NULL,
                items_count INT NOT NULL DEFAULT 0,
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                items_json TEXT NULL,
                status ENUM('Pending','Processing','Shipped','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_order_number (order_number),
                INDEX idx_customer_email (customer_email),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
    } catch (Exception $e) {
        // Silently fail - tables might already exist
        error_log("Database initialization error: " . $e->getMessage());
    }
}

