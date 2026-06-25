<?php
session_start();
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Adding Sample Products (No Images)</h1>";
    
    $pdo->exec("DELETE FROM products");
    $pdo->exec("DELETE FROM categories");
    
    //  sample categories 
    $categories = [
        ['name' => 'Electronics', 'description' => 'Latest gadgets and electronics'],
        ['name' => 'Clothing', 'description' => 'Fashion and apparel'],
        ['name' => 'Home & Garden', 'description' => 'Home improvement and garden supplies'],
        ['name' => 'Sports', 'description' => 'Sports equipment and accessories'],
        ['name' => 'Books', 'description' => 'Books and educational materials']
    ];
    
    $category_ids = [];
    foreach ($categories as $category) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$category['name'], $category['description']]);
        $category_ids[$category['name']] = $pdo->lastInsertId();
        echo "<p>✅ Added category: {$category['name']}</p>";
    }
    
    // Sample products 
    $products = [
        [
            'name' => 'iPhone 15 Pro',
            'description' => 'Latest Apple iPhone with advanced camera system',
            'price' => 999.99,
            'category' => 'Electronics',
            'stock' => 50
        ],
        [
            'name' => 'Samsung Galaxy S24',
            'description' => 'Powerful Android smartphone with amazing display',
            'price' => 849.99,
            'category' => 'Electronics',
            'stock' => 30
        ],
        [
            'name' => 'Nike Air Max',
            'description' => 'Comfortable running shoes with air cushioning',
            'price' => 129.99,
            'category' => 'Clothing',
            'stock' => 100
        ],
        [
            'name' => 'Adidas T-Shirt',
            'description' => 'Cotton t-shirt with classic Adidas design',
            'price' => 29.99,
            'category' => 'Clothing',
            'stock' => 200
        ],
        [
            'name' => 'Kitchen Blender',
            'description' => 'High-speed blender for smoothies and food processing',
            'price' => 79.99,
            'category' => 'Home & Garden',
            'stock' => 25
        ],
        [
            'name' => 'Garden Tools Set',
            'description' => 'Complete set of gardening tools for home use',
            'price' => 49.99,
            'category' => 'Home & Garden',
            'stock' => 40
        ],
        [
            'name' => 'Basketball',
            'description' => 'Official size basketball for indoor and outdoor use',
            'price' => 24.99,
            'category' => 'Sports',
            'stock' => 75
        ],
        [
            'name' => 'Yoga Mat',
            'description' => 'Non-slip yoga mat for exercise and meditation',
            'price' => 19.99,
            'category' => 'Sports',
            'stock' => 60
        ],
        [
            'name' => 'Programming Book',
            'description' => 'Learn web development with this comprehensive guide',
            'price' => 39.99,
            'category' => 'Books',
            'stock' => 35
        ]
    ];
    
    $added_products = 0;
    foreach ($products as $product) {
        $category_id = $category_ids[$product['category']];
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, stock_quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $product['name'],
            $product['description'],
            $product['price'],
            $category_id,
            $product['stock']
        ]);
        $added_products++;
        echo "<p>✅ Added product: {$product['name']} - \${$product['price']}</p>";
    }
    
    echo "<h2 style='color: green;'>✅ Successfully added $added_products sample products!</h2>";
    echo "<p><a href='products.php'>View Products Page</a> | <a href='index.html'>Home</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>