<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create cart table
        $cart_sql = "
        CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            product_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            total_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id)
        )";

        $conn->exec($cart_sql);
        
        // Create sample products if they don't exist
        $sample_products = [
            ['Wireless Bluetooth Headphones', 199.99, 'High-quality wireless headphones with noise cancellation', 'electronics'],
            ['Smartphone Case', 50.00, 'Durable smartphone case with drop protection', 'accessories'],
            ['Laptop Sleeve', 29.99, 'Protective laptop sleeve with padding', 'accessories'],
            ['USB-C Cable', 15.00, 'Fast charging USB-C cable 2m length', 'electronics'],
            ['Screen Protector', 12.50, 'Tempered glass screen protector', 'accessories']
        ];
        
        foreach ($sample_products as $product) {
            $check_stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
            $check_stmt->execute([$product[0]]);
            $exists = $check_stmt->fetch();
            
            if (!$exists) {
                $insert_stmt = $conn->prepare("
                    INSERT INTO products (name, price, description, category, image, stock_quantity) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->execute([
                    $product[0], 
                    $product[1], 
                    $product[2], 
                    $product[3],
                    '',
                    100
                ]);
            }
        }
        
        $success = "Cart system setup successfully! Tables created and sample products added.";
        
    } catch (PDOException $e) {
        $error = "Error setting up cart system: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Cart System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Setup Cart System</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                            <a href="products.php" class="btn btn-primary">View Products</a>
                            <a href="debug_products.php" class="btn btn-secondary">Check Products</a>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <p>This will create the cart table and add sample products with consistent pricing.</p>
                        
                        <h5>Sample Products:</h5>
                        <ul>
                            <li>Wireless Bluetooth Headphones - $199.99</li>
                            <li>Smartphone Case - $50.00</li>
                            <li>Laptop Sleeve - $29.99</li>
                            <li>USB-C Cable - $15.00</li>
                            <li>Screen Protector - $12.50</li>
                        </ul>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary">Setup Cart System</button>
                            <a href="debug_products.php" class="btn btn-secondary">Check Current Status</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>