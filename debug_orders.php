<?php
session_start();
include 'config.php';

echo "<h3>Debug Orders Data</h3>";

try {
    // Check orders table
    $orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>All Orders:</h4>";
    echo "<pre>";
    print_r($orders);
    echo "</pre>";
    
    // Check order_items table
    $order_items = $conn->query("SELECT * FROM order_items ORDER BY order_id")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>All Order Items:</h4>";
    echo "<pre>";
    print_r($order_items);
    echo "</pre>";
    
    // Check if there are duplicate entries
    $duplicates = $conn->query("
        SELECT order_id, product_name, COUNT(*) as count 
        FROM order_items 
        GROUP BY order_id, product_name 
        HAVING count > 1
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Duplicate Items:</h4>";
    echo "<pre>";
    print_r($duplicates);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>