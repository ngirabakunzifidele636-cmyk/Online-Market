<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simple database connection
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = 'testuser';
    $email = 'test@example.com';
    $password = 'password123';
    
    echo "<h1>Quick Registration Test</h1>";
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert test user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, email_verified) VALUES (?, ?, ?, TRUE)");
        $stmt->execute([$username, $email, $passwordHash]);
        
        $userId = $pdo->lastInsertId();
        
        echo "<p style='color: green;'>✅ Test user created! ID: $userId</p>";
        
        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        
        echo "<p style='color: green;'>✅ Session created!</p>";
        
        // Show login info
        echo "<h3>Test Login Credentials:</h3>";
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        
        echo "<p><a href='simple_login.php'>Test Login Now</a></p>";
        
    } catch(PDOException $e) {
        echo "<p style='color: red;'>❌ Failed: " . $e->getMessage() . "</p>";
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Quick Test</title>
    </head>
    <body>
        <h2>Quick Database Test</h2>
        <p>This will create a test user automatically.</p>
        <form method="POST">
            <button type="submit">Create Test User</button>
        </form>
        <p><a href="debug_users.php">Check Users</a></p>
    </body>
    </html>
    <?php
}
?>