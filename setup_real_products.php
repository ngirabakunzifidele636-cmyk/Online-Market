<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create real products that customers can buy
        $real_products = [
            [
                'name' => 'iPhone 15 Pro',
                'price' => 999.99,
                'description' => 'Latest iPhone with advanced camera and A17 Pro chip',
                'category' => 'electronics',
                'image' => 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=400&h=300&fit=crop'
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'price' => 849.99,
                'description' => 'Powerful Android smartphone with AI features',
                'category' => 'electronics',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=300&fit=crop'
            ],
            [
                'name' => 'MacBook Air M3',
                'price' => 1099.99,
                'description' => 'Lightweight laptop with M3 chip for ultimate performance',
                'category' => 'electronics',
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=300&fit=crop'
            ],
            [
                'name' => 'Wireless Headphones',
                'price' => 199.99,
                'description' => 'Noise-cancelling Bluetooth headphones',
                'category' => 'electronics',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop'
            ],
            [
                'name' => 'Smart Watch',
                'price' => 299.99,
                'description' => 'Fitness tracking and smart notifications',
                'category' => 'electronics',
                'image' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop'
            ],
            [
                'name' => 'Gaming Mouse',
                'price' => 79.99,
                'description' => 'High-precision gaming mouse with RGB lighting',
                'category' => 'electronics',
                'image' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=400&h=300&fit=crop'
            ]
        ];
        
        foreach ($real_products as $product) {
            // Check if product already exists
            $check_stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
            $check_stmt->execute([$product['name']]);
            $exists = $check_stmt->fetch();
            
            if (!$exists) {
                $insert_stmt = $conn->prepare("
                    INSERT INTO products (name, price, description, category, image, stock_quantity) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->execute([
                    $product['name'], 
                    $product['price'], 
                    $product['description'], 
                    $product['category'],
                    $product['image'],
                    50  // Stock quantity
                ]);
            }
        }
        
        $success = "Real products added successfully! Customers can now browse and order these products.";
        
    } catch (PDOException $e) {
        $error = "Error adding products: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Real Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Add Real Products for Customers</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                            <a href="products.php" class="btn btn-primary">View Products</a>
                            <a href="index.php" class="btn btn-secondary">Go to Website</a>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <p>This will add real products that customers can browse and purchase.</p>
                        
                        <h5>Products that will be added:</h5>
                        <div class="row">
                            <?php foreach ($real_products as $product): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <img src="<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                                        <div class="card-body">
                                            <h6><?= $product['name'] ?></h6>
                                            <p class="text-success"><strong>$<?= number_format($product['price'], 2) ?></strong></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Add Real Products for Customers
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>