<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Updating Users Table</h1>";
    
    // Add missing columns for password reset
    $alter_queries = [
        "ALTER TABLE users ADD COLUMN reset_token VARCHAR(100) NULL AFTER verification_token_expires",
        "ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL AFTER reset_token",
        "CREATE INDEX IF NOT EXISTS idx_reset_token ON users(reset_token)"
    ];
    
    foreach ($alter_queries as $query) {
        try {
            $pdo->exec($query);
            echo "<p style='color: green;'>✅ Executed: " . htmlspecialchars($query) . "</p>";
        } catch(PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Could not execute: " . htmlspecialchars($query) . "<br>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verify the table structure
    echo "<h2>Current Users Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 style='color: green;'>🎉 Users table update completed!</h2>";
    echo "<p><a href='forgot_password.php'>Test Password Reset</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>