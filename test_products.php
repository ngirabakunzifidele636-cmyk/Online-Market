<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    // Simple query without joins first
    $stmt = $pdo->query("SELECT * FROM products WHERE is_active = TRUE LIMIT 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Products</title>
    <style>
        .product { border: 1px solid #ccc; padding: 10px; margin: 10px; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Test Products Page</h1>
    
    <?php if(empty($products)): ?>
        <p class="error">No products found in database!</p>
        <p><a href="add_sample_products.php">Add Sample Products</a></p>
    <?php else: ?>
        <p>Found <?php echo count($products); ?> products:</p>
        <?php foreach($products as $product): ?>
            <div class="product">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>Price: $<?php echo $product['price']; ?></p>
                <p>Stock: <?php echo $product['stock_quantity']; ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <p><a href="debug_products.php">Debug Database</a></p>
</body>
</html>