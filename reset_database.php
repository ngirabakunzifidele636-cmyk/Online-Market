<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Resetting Entire Database</h1>";
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop tables in correct order (child tables first)
    $tables = ['cart', 'orders', 'order_items', 'payments', 'user_sessions', 'products', 'categories', 'users'];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table");
            echo "<p>✅ Dropped table: $table</p>";
        } catch(PDOException $e) {
            echo "<p>⚠️ Could not drop $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Now recreate all tables in correct order
    echo "<h2>Recreating Tables...</h2>";
    
    // 1. Users table
    $pdo->exec("
        CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            country VARCHAR(50),
            postal_code VARCHAR(20),
            profile_image VARCHAR(255),
            verification_token VARCHAR(100) NULL,
            verification_token_expires DATETIME NULL,
            email_verified BOOLEAN DEFAULT FALSE,
            verified_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Created users table</p>";
    
    // 2. Categories table
    $pdo->exec("
        CREATE TABLE categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            parent_id INT,
            image VARCHAR(255),
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Created categories table</p>";
    
    // 3. Products table
    $pdo->exec("
        CREATE TABLE products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            short_description VARCHAR(500),
            price DECIMAL(10,2) NOT NULL,
            compare_price DECIMAL(10,2),
            cost_price DECIMAL(10,2),
            sku VARCHAR(100) UNIQUE,
            barcode VARCHAR(100),
            weight DECIMAL(8,2),
            dimensions VARCHAR(100),
            main_image VARCHAR(255),
            image_gallery JSON,
            stock_quantity INT DEFAULT 0,
            low_stock_threshold INT DEFAULT 5,
            category_id INT,
            brand VARCHAR(100),
            supplier_id INT,
            is_featured BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            tags JSON,
            specifications JSON,
            view_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");
    echo "<p>✅ Created products table</p>";
    
    // 4. Cart table
    $pdo->exec("
        CREATE TABLE cart (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            attributes JSON,
            session_id VARCHAR(100),
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Created cart table</p>";
    
    // Create indexes
    $pdo->exec("CREATE INDEX idx_verification_token ON users(verification_token)");
    $pdo->exec("CREATE INDEX idx_user_email ON users(email)");
    $pdo->exec("CREATE INDEX idx_category ON products(category_id)");
    $pdo->exec("CREATE INDEX idx_cart_user ON cart(user_id)");
    
    echo "<p>✅ Created all indexes</p>";
    
    echo "<h2 style='color: green;'>🎉 Database reset successfully!</h2>";
    echo "<p><a href='add_sample_products.php'>Add Sample Products</a></p>";
    echo "<p><a href='simple_register.php'>Test Registration</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>