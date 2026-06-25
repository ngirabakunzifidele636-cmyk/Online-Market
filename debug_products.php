<?php
require_once 'config.php';

try {
    // $pdo = getDatabaseConnection();
    
    // echo "<h1>Database Debug Info</h1>";
    
    // // Check if products table has data
    // $stmt = $pdo->query("SELECT COUNT(*) as product_count FROM products");
    // $product_count = $stmt->fetchColumn();
    // echo "<p>Total products in database: <strong>$product_count</strong></p>";
    
    // // Check if categories table has data
    // $stmt = $pdo->query("SELECT COUNT(*) as category_count FROM categories");
    // $category_count = $stmt->fetchColumn();
    // echo "<p>Total categories in database: <strong>$category_count</strong></p>";
    
    // Show all products with details
    echo "<h2>All Products:</h2>";
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p style='color: red;'>No products found in database!</p>";
        echo "<p><a href='add_sample_products.php'>Click here to add sample products</a></p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Category</th><th>Stock</th><th>Active</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>\${$product['price']}</td>";
            echo "<td>{$product['category_name']}</td>";
            echo "<td>{$product['stock_quantity']}</td>";
            echo "<td>{$product['is_active']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch(PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>