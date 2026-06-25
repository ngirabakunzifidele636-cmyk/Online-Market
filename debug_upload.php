<?php
include 'config.php';

echo "<h3>Debug Products Data</h3>";

try {
    // Check products table
    $products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>All Products:</h4>";
    echo "<pre>";
    print_r($products);
    echo "</pre>";
    
    // Check cart table if exists
    $cart_table = $conn->query("SHOW TABLES LIKE 'cart'")->fetch();
    if ($cart_table) {
        $cart_items = $conn->query("SELECT * FROM cart")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Cart Items:</h4>";
        echo "<pre>";
        print_r($cart_items);
        echo "</pre>";
    } else {
        echo "<p>Cart table doesn't exist</p>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>