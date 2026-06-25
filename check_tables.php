<?php
include 'config.php';

echo "<h3>Checking Database Tables</h3>";

try {
    // Check if tables exist
    $tables = ['users', 'products', 'orders', 'order_items'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        echo "<p>Table '$table': " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "</p>";
        
        if ($exists) {
            // Show table structure
            $columns = $conn->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>Structure: ";
            print_r($columns);
            echo "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>