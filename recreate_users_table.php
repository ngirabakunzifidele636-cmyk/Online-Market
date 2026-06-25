<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Recreating Users Table</h1>";
    
    // Drop table if exists
    $pdo->exec("DROP TABLE IF EXISTS users");
    echo "<p>✅ Dropped existing users table</p>";
    
    // Create new users table
    $createTableSQL = "
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
    )";
    
    $pdo->exec($createTableSQL);
    echo "<p>✅ Created new users table with correct structure</p>";
    
    // Create indexes
    $pdo->exec("CREATE INDEX idx_verification_token ON users(verification_token)");
    $pdo->exec("CREATE INDEX idx_verification_expires ON users(verification_token_expires)");
    $pdo->exec("CREATE INDEX idx_user_email ON users(email)");
    
    echo "<p>✅ Created all necessary indexes</p>";
    
    echo "<h2 style='color: green;'>🎉 Users table recreated successfully!</h2>";
    echo "<p><a href='test_register.html'>Test Registration Now</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>