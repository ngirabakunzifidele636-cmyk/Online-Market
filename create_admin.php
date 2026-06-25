<?php
session_start();
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Creating Admin User</h1>";
    
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@onlinemarket.com'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p>Admin user already exists!</p>";
    } else {
        // Create admin user
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, email_verified, is_active) VALUES (?, ?, ?, ?, ?, TRUE, TRUE)");
        $stmt->execute(['admin', 'admin@onlinemarket.com', $passwordHash, 'System', 'Administrator']);
        
        $adminId = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Admin user created successfully! ID: $adminId</p>";
    }
    
    // Show admin credentials
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>Admin Login Credentials:</h3>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Email:</strong> admin@onlinemarket.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "</div>";
    
    echo "<p><a href='admin_login.php'>Go to Admin Login</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>