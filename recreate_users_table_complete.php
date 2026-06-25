<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Recreating Users Table with All Features</h1>";
    
    // First, backup important data if needed
    echo "<p>Backing up user data...</p>";
    $backup_stmt = $pdo->query("SELECT id, username, email, password_hash, first_name, last_name, phone, email_verified, is_active, created_at FROM users");
    $users_backup = $backup_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop and recreate table
    $pdo->exec("DROP TABLE IF EXISTS users");
    
    $create_table_sql = "
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
        reset_token VARCHAR(100) NULL,
        reset_token_expires DATETIME NULL,
        email_verified BOOLEAN DEFAULT FALSE,
        verified_at TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($create_table_sql);
    echo "<p style='color: green;'>✅ Created new users table</p>";
    
    // Create indexes
    $pdo->exec("CREATE INDEX idx_verification_token ON users(verification_token)");
    $pdo->exec("CREATE INDEX idx_verification_expires ON users(verification_token_expires)");
    $pdo->exec("CREATE INDEX idx_reset_token ON users(reset_token)");
    $pdo->exec("CREATE INDEX idx_user_email ON users(email)");
    echo "<p style='color: green;'>✅ Created all indexes</p>";
    
    // Restore user data
    if (!empty($users_backup)) {
        echo "<p>Restoring user data...</p>";
        $insert_stmt = $pdo->prepare("
            INSERT INTO users (id, username, email, password_hash, first_name, last_name, phone, email_verified, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $restored_count = 0;
        foreach ($users_backup as $user) {
            try {
                $insert_stmt->execute([
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $user['password_hash'],
                    $user['first_name'],
                    $user['last_name'],
                    $user['phone'],
                    $user['email_verified'],
                    $user['is_active'],
                    $user['created_at']
                ]);
                $restored_count++;
            } catch(PDOException $e) {
                echo "<p style='color: orange;'>⚠️ Could not restore user {$user['username']}: " . $e->getMessage() . "</p>";
            }
        }
        echo "<p style='color: green;'>✅ Restored $restored_count users</p>";
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create admin user if it doesn't exist
    $check_admin = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $check_admin->execute();
    
    if ($check_admin->rowCount() === 0) {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, email_verified, is_active) VALUES (?, ?, ?, ?, ?, TRUE, TRUE)");
        $stmt->execute(['admin', 'admin@onlinemarket.com', $passwordHash, 'System', 'Administrator']);
        echo "<p style='color: green;'>✅ Created admin user</p>";
    }
    
    echo "<h2 style='color: green;'>🎉 Users table recreation completed successfully!</h2>";
    echo "<p><a href='forgot_password.php'>Test Password Reset</a> | <a href='admin_login.php'>Admin Login</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>