<?php
// simple_test.php
session_start();

$host = 'localhost';
$dbname = 'online_market';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Test registration
    $test_username = 'testuser';
    $test_email = 'test@example.com';
    $test_password = password_hash('password123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$test_username, $test_email, $test_password]);
    
    echo "✅ Test registration successful!<br>";
    echo "User created: $test_username<br>";
    echo "You can now try the registration form.";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>