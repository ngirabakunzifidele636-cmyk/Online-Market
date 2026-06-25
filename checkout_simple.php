<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// Get cart items
try {
    $cart_stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $cart_stmt->execute([$user_id]);
    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cart_items)) {
        header('Location: cart.php');
        exit();
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['total_price'];
    }
    $tax_amount = $subtotal * 0.08;
    $shipping_amount = $subtotal > 100 ? 0 : 5.99;
    $total_amount = $subtotal + $tax_amount + $shipping_amount;
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'address', 'city', 'state', 'zip_code'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Generate order number
        $order_number = 'ORD' . date('YmdHis') . rand(100, 999);

        // Create shipping address
        $shipping_address = "{$_POST['first_name']} {$_POST['last_name']}\n";
        $shipping_address .= "{$_POST['address']}\n";
        $shipping_address .= "{$_POST['city']}, {$_POST['state']} {$_POST['zip_code']}\n";
        if (!empty($_POST['phone'])) {
            $shipping_address .= "Phone: {$_POST['phone']}\n";
        }
        $shipping_address .= "Email: {$_POST['email']}";

        // Insert order
        $order_stmt = $conn->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, status, shipping_address, payment_method, payment_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $order_stmt->execute([
            $user_id,
            $order_number,
            $total_amount,
            'confirmed',
            $shipping_address,
            'Credit Card',
            'paid'
        ]);
        
        $order_id = $conn->lastInsertId();
        
        // Insert order items
        foreach ($cart_items as $item) {
            $order_item_stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $order_item_stmt->execute([
                $order_id,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['product_price'],
                $item['total_price']
            ]);
        }
        
        // Clear cart
        $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_cart_stmt->execute([$user_id]);
        
        // Redirect to success page
        $_SESSION['order_success'] = true;
        $_SESSION['order_number'] = $order_number;
        header("Location: order_success.php");
        exit();
        
    } catch (Exception $e) {
        $error = "Order failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <h1>Checkout</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address *</label>
                                <input type="text" class="form-control" name="address" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City *</label>
                                    <input type="text" class="form-control" name="city" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">State *</label>
                                    <input type="text" class="form-control" name="state" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">ZIP Code *</label>
                                    <input type="text" class="form-control" name="zip_code" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Card Number *</label>
                                <input type="text" class="form-control" name="card_number" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Expiry Date *</label>
                                        <input type="text" class="form-control" name="expiry_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">CVV *</label>
                                        <input type="text" class="form-control" name="cvv" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Name on Card *</label>
                                <input type="text" class="form-control" name="card_name" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <small><?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?></small>
                                    </div>
                                    <small>$<?= number_format($item['total_price'], 2) ?></small>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span><?= $shipping_amount == 0 ? 'FREE' : '$' . number_format($shipping_amount, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (8%):</span>
                                <span>$<?= number_format($tax_amount, 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span>$<?= number_format($total_amount, 2) ?></span>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 mt-3">
                                Place Order & Pay
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>
</html>