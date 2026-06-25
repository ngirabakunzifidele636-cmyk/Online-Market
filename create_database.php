<?php
echo "<h1>Creating Online Market Database</h1>";

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS online_market");
    echo "<p style='color: green;'>✅ Database 'online_market' created successfully!</p>";
    
    // Select the database
    $pdo->exec("USE online_market");
    
    // Create tables
    $tables_sql = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
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
        )",
        
        "products" => "CREATE TABLE IF NOT EXISTS products (
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "categories" => "CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            parent_id INT,
            image VARCHAR(255),
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "cart" => "CREATE TABLE IF NOT EXISTS cart (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            attributes JSON,
            session_id VARCHAR(100),
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    // Execute each table creation
    foreach ($tables_sql as $table_name => $sql) {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Table '$table_name' created successfully!</p>";
    }
    
    // Add indexes
    $indexes_sql = [
        "CREATE INDEX IF NOT EXISTS idx_verification_token ON users(verification_token)",
        "CREATE INDEX IF NOT EXISTS idx_verification_expires ON users(verification_token_expires)",
        "CREATE INDEX IF NOT EXISTS idx_user_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_category ON products(category_id)",
        "CREATE INDEX IF NOT EXISTS idx_price ON products(price)",
        "CREATE INDEX IF NOT EXISTS idx_featured ON products(is_featured)",
        "CREATE INDEX IF NOT EXISTS idx_parent ON categories(parent_id)",
        "CREATE INDEX IF NOT EXISTS idx_cart_user ON cart(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_cart_session ON cart(session_id)"
    ];
    
    foreach ($indexes_sql as $sql) {
        $pdo->exec($sql);
    }
    echo "<p style='color: green;'>✅ All indexes created successfully!</p>";
    
    echo "<h2 style='color: green;'>🎉 Database setup completed successfully!</h2>";
    echo "<p><a href='test.php'>Test Database Connection</a></p>";
    echo "<p><a href='register.html'>Go to Registration</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>