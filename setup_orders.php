<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create orders table if it doesn't exist
        $orders_sql = "
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($orders_sql);

        // Create order_items table if it doesn't exist
        $order_items_sql = "
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($order_items_sql);
        
        // Add missing columns safely
        $alter_queries = [
            "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity",
            "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER unit_price"
        ];
        
        foreach ($alter_queries as $query) {
            try {
                $conn->exec($query);
            } catch (PDOException $e) {
                // Ignore errors if columns already exist
                if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                    throw $e;
                }
            }
        }
        
        $success = "Orders system setup successfully!";
        
    } catch (PDOException $e) {
        $error = "Error setting up orders: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Orders - TechShop</title>
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
                            <a href="check_orders.php" class="btn btn-primary">Check Orders</a>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <p>This will safely add missing columns to your orders system without deleting any data.</p>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary">Setup Orders System</button>
                            <a href="check_orders.php" class="btn btn-secondary">Check Current Status</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>