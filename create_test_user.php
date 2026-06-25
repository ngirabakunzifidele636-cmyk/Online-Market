<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Use correct database credentials
$host = 'localhost';
$dbname = 'online_market';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create test user
$test_username = 'testuser';
$test_email = 'test@gmail.com';
$test_password = 'password123';

echo "<h1>Creating Test User</h1>";

// Check if user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$test_username, $test_email]);

if ($stmt->rowCount() > 0) {
    echo "<p>⚠️ Test user already exists</p>";
} else {
    // Hash password
    $passwordHash = password_hash($test_password, PASSWORD_DEFAULT);
    
    // Insert test user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, email_verified) VALUES (?, ?, ?, ?, TRUE)");
        $stmt->execute([$test_username, $test_email, $passwordHash, 'Test User']);
        
        $userId = $pdo->lastInsertId();
        
        echo "<p style='color: green;'>✅ Test user created successfully! ID: $userId</p>";
    } catch(PDOException $e) {
        echo "<p style='color: red;'>❌ Failed to create user: " . $e->getMessage() . "</p>";
    }
}

// Show current users
echo "<h2>Current Users in Database:</h2>";
$stmt = $pdo->query("SELECT id, username, email, email_verified FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "<p>No users found</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Verified</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>" . ($user['email_verified'] ? '✅ Yes' : '❌ No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Show login instructions
echo "<h2>Login Instructions:</h2>";
echo "<p><strong>Use these credentials to login:</strong></p>";
echo "<ul>";
echo "<li><strong>Username:</strong> $test_username</li>";
echo "<li><strong>Email:</strong> $test_email</li>";
echo "<li><strong>Password:</strong> $test_password</li>";
echo "</ul>";

echo "<p><a href='simple_login.php'>Click here to login</a></p>";
echo "<p><a href='debug_users.php'>Check all users</a></p>";
?>