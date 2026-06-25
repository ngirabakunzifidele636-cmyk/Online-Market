<?php
echo "<h1>Online Market - System Test</h1>";
echo "<p>Server time: " . date('Y-m-d H:i:s') . "</p>";

// Test database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=online_market", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if tables exist
    $tables = ['users', 'products', 'categories', 'cart'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Table '$table' missing</p>";
        }
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p><a href='create_database.php' style='color: blue;'>Click here to create the database</a></p>";
}

// Test PHP configuration
echo "<h2>PHP Configuration:</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>PHP Extensions: ";
echo extension_loaded('pdo_mysql') ? "✅ PDO_MySQL" : "❌ PDO_MySQL";
echo "</p>";

// Test file permissions
echo "<h2>File System:</h2>";
$files = ['index.html', 'register.html', 'register.php', 'login.html', 'login.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file - EXISTS</p>";
    } else {
        echo "<p style='color: red;'>❌ $file - MISSING</p>";
    }
}
?>