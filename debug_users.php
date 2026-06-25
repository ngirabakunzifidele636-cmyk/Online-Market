<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Users Database Debug</h1>";
    
    // Check if users table has data
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $user_count = $stmt->fetchColumn();
    echo "<p>Total users in database: <strong>$user_count</strong></p>";
    
    // Show all users with details
    echo "<h2>All Users:</h2>";
    $stmt = $pdo->query("SELECT id, username, email, email_verified, is_active, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p style='color: red;'>No users found in database!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Email Verified</th><th>Active</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>" . ($user['email_verified'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "<td>" . ($user['is_active'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch(PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>