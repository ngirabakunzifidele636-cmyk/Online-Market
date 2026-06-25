<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// First, check if tables exist
try {
    $orders_table = $conn->query("SHOW TABLES LIKE 'orders'")->fetch();
    $order_items_table = $conn->query("SHOW TABLES LIKE 'order_items'")->fetch();
    
    if (!$orders_table || !$order_items_table) {
        $error = "Orders tables are not set up. Please run the setup first.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    try {
        // Generate unique order number
        $order_number = 'TS' . date('YmdHis') . rand(100, 999);
        
        // Calculate amounts
        $subtotal = 379.98;
        $tax_amount = 30.00;
        $shipping_amount = 0;
        $total_amount = $subtotal + $tax_amount + $shipping_amount;
        
        // Create test order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, subtotal, tax_amount, shipping_amount, status, shipping_address, payment_method, payment_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = 'processing';
        $shipping_address = "123 Test Street, Test City, TC 12345\nUnited States";
        $payment_method = 'Credit Card';
        $payment_status = 'paid';
        
        $stmt->execute([
            $user_id, 
            $order_number, 
            $total_amount,
            $subtotal,
            $tax_amount,
            $shipping_amount, 
            $status, 
            $shipping_address, 
            $payment_method, 
            $payment_status
        ]);
        $order_id = $conn->lastInsertId();
        
        // Add DIFFERENT order items
        $items = [
            [1, 'Wireless Bluetooth Headphones', 1, 199.99, 199.99],
            [2, 'Smartphone Case', 2, 50.00, 100.00],
            [3, 'Laptop Sleeve', 1, 29.99, 29.99],
            [4, 'USB-C Cable', 3, 15.00, 45.00],
            [5, 'Screen Protector', 2, 12.50, 25.00]
        ];
        
        foreach ($items as $item) {
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $item[0], $item[1], $item[2], $item[3], $item[4]]);
        }
        
        $success = "Test order created successfully!<br>
                   Order Number: <strong>$order_number</strong><br>
                   Total: <strong>$$total_amount</strong><br>
                   Items: <strong>" . count($items) . " different products</strong><br>
                   Order ID: <strong>$order_id</strong>";
        
    } catch (PDOException $e) {
        $error = "Error creating test order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Test Order - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Create Test Order</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="profile.php" class="btn btn-primary">View Profile</a>
                                <a href="orders.php" class="btn btn-secondary">View All Orders</a>
                                <a href="order_details.php?order_id=<?= $order_id ?>" class="btn btn-info">View This Order</a>
                                <a href="dashboard.php" class="btn btn-warning">Admin Dashboard</a>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                            <?php if (strpos($error, 'not set up') !== false): ?>
                                <a href="setup_orders_system.php" class="btn btn-warning">Setup Orders System</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!$success && !$error): ?>
                            <p>This will create a sample order with <strong>5 different products</strong> for testing purposes.</p>
                            
                            <div class="mb-4">
                                <h5>Order Items that will be created:</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Wireless Bluetooth Headphones</span>
                                        <span>$199.99 x 1 = $199.99</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Smartphone Case</span>
                                        <span>$50.00 x 2 = $100.00</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Laptop Sleeve</span>
                                        <span>$29.99 x 1 = $29.99</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>USB-C Cable</span>
                                        <span>$15.00 x 3 = $45.00</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Screen Protector</span>
                                        <span>$12.50 x 2 = $25.00</span>
                                    </li>
                                    <li class="list-group-item list-group-item-primary d-flex justify-content-between">
                                        <strong>Total</strong>
                                        <strong>$399.98</strong>
                                    </li>
                                </ul>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus"></i> Create Test Order with 5 Different Items
                                </button>
                                <a href="setup_orders_system.php" class="btn btn-secondary">Setup System First</a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>