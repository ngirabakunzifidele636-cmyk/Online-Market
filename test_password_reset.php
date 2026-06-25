<?php
session_start();
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Password Reset System Test</h1>";
    
    // Check if reset_token column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    $reset_token_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reset_token_exists) {
        echo "<p style='color: red;'>❌ reset_token column is missing from users table</p>";
        echo "<p><a href='update_users_table.php'>Run Table Update</a></p>";
        exit();
    }
    
    echo "<p style='color: green;'>✅ reset_token column exists</p>";
    
    // Check if reset_token_expires column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token_expires'");
    $reset_token_expires_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reset_token_expires_exists) {
        echo "<p style='color: red;'>❌ reset_token_expires column is missing from users table</p>";
        echo "<p><a href='update_users_table.php'>Run Table Update</a></p>";
        exit();
    }
    
    echo "<p style='color: green;'>✅ reset_token_expires column exists</p>";
    
    // Test generating a reset token
    $test_email = 'test@gmail.com'; // Use your test user's email
    
    $check_user = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check_user->execute([$test_email]);
    $user_exists = $check_user->fetch(PDO::FETCH_ASSOC);
    
    if ($user_exists) {
        $reset_token = bin2hex(random_bytes(32));
        $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $update_stmt->execute([$reset_token, $token_expires, $test_email]);
        
        echo "<p style='color: green;'>✅ Successfully generated reset token for user: $test_email</p>";
        echo "<p><strong>Reset Token:</strong> " . substr($reset_token, 0, 20) . "...</p>";
        echo "<p><strong>Expires:</strong> $token_expires</p>";
        
        // Test the reset link
        $reset_link = "http://localhost/online_market/reset_password.php?token=$reset_token";
        echo "<p><strong>Test Reset Link:</strong> <a href='$reset_link' target='_blank'>$reset_link</a></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Test user with email '$test_email' not found. Using first available user.</p>";
        
        // Use first user
        $first_user = $pdo->query("SELECT email FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($first_user) {
            $test_email = $first_user['email'];
            
            $reset_token = bin2hex(random_bytes(32));
            $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
            $update_stmt->execute([$reset_token, $token_expires, $test_email]);
            
            echo "<p style='color: green;'>✅ Successfully generated reset token for user: $test_email</p>";
            echo "<p><strong>Reset Token:</strong> " . substr($reset_token, 0, 20) . "...</p>";
            echo "<p><strong>Expires:</strong> $token_expires</p>";
            
            $reset_link = "http://localhost/online_market/reset_password.php?token=$reset_token";
            echo "<p><strong>Test Reset Link:</strong> <a href='$reset_link' target='_blank'>$reset_link</a></p>";
        } else {
            echo "<p style='color: red;'>❌ No users found in database</p>";
        }
    }
    
    echo "<h2 style='color: green;'>🎉 Password Reset System Test Complete!</h2>";
    echo "<p><a href='forgot_password.php'>Go to Password Reset Page</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>