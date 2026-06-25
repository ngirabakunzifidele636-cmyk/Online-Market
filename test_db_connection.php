<?php
// Test database connection with different credentials
$host = 'localhost';
$dbname = 'online_market';

// Try different username/password combinations
$credentials = [
    ['root', ''],      // Default XAMPP
    ['root', 'root'],  // Some XAMPP setups
    ['', ''],          // No username
];

echo "<h1>Testing Database Connection</h1>";

foreach ($credentials as $cred) {
    $username = $cred[0];
    $password = $cred[1];
    
    echo "<h2>Trying: username='$username', password='$password'</h2>";
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test if we can query
        $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
        $user_count = $stmt->fetchColumn();
        
        echo "<p style='color: green;'>✅ SUCCESS! Connected to database. Users found: $user_count</p>";
        echo "<p><strong>Use these credentials in config.php:</strong></p>";
        echo "<p>DB_USER = '$username'</p>";
        echo "<p>DB_PASS = '$password'</p>";
        break;
        
    } catch(PDOException $e) {
        echo "<p style='color: red;'>❌ FAILED: " . $e->getMessage() . "</p>";
    }
}
?>
