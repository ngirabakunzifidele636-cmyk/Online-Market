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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Generate unique order number
        $order_number = 'TS' . date('YmdHis') . rand(100, 999);
        
        // Create test order with different items
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, status, shipping_address, payment_method, payment_status, tax_amount, shipping_amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $total_amount = 379.98; // Updated total for different items
        $status = 'processing';
        $shipping_address = "123 Test Street, Test City, TC 12345";
        $payment_method = 'Credit Card';
        $payment_status = 'paid';
        $tax_amount = 30.00;
        $shipping_amount = 0;
        
        $stmt->execute([
            $user_id, 
            $order_number, 
            $total_amount, 
            $status, 
            $shipping_address, 
            $payment_method, 
            $payment_status,
            $tax_amount,
            $shipping_amount
        ]);
        $order_id = $conn->lastInsertId();
        
        // Add DIFFERENT order items with unique product_ids
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
        
        $success = "Test order created successfully!<br>Order Number: <strong>$order_number</strong><br>Total: <strong>$$total_amount</strong><br>Items: <strong>" . count($items) . " different products</strong>";
        
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
                        <h4>Create Test Order with Multiple Items</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                            <div class="d-flex gap-2">
                                <a href="profile.php" class="btn btn-primary">View Profile</a>
                                <a href="orders.php" class="btn btn-secondary">View Orders</a>
                                <a href="order_details.php?order_id=<?= $order_id ?? '' ?>" class="btn btn-info">View This Order</a>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <p>This will create a sample order with <strong>5 different products</strong> for testing purposes.</p>
                        
                        <div class="mb-4">
                            <h5>Order Items that will be created:</h5>
                            <ul class="list-group">
                                <li class="list-group-item">Wireless Bluetooth Headphones - $199.99 x 1</li>
                                <li class="list-group-item">Smartphone Case - $50.00 x 2</li>
                                <li class="list-group-item">Laptop Sleeve - $29.99 x 1</li>
                                <li class="list-group-item">USB-C Cable - $15.00 x 3</li>
                                <li class="list-group-item">Screen Protector - $12.50 x 2</li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary">Create Test Order with Multiple Items</button>
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>