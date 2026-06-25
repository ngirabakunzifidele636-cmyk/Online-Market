<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create orders table
        $orders_sql = "
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";

        // Create order_items table
        $order_items_sql = "
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";

        $conn->exec($orders_sql);
        $conn->exec($order_items_sql);
        
        $success = "Orders system setup successfully! Tables created.";
        
    } catch (PDOException $e) {
        $error = "Error setting up orders system: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Orders System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Setup Orders System</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                            <a href="create_test_order.php" class="btn btn-primary">Create Test Order</a>
                            <a href="check_tables.php" class="btn btn-secondary">Check Tables</a>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <p>This will create the necessary database tables for the orders system.</p>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary">Create Orders Tables</button>
                            <a href="check_tables.php" class="btn btn-secondary">Check Current Status</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>