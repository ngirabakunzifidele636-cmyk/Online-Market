<?php
session_start();
require_once 'config.php';

// Check if user is logged in and has pending order
$_SESSION['order_success'] = true;
$_SESSION['order_number'] = $order_data['order_number'];
$_SESSION['order_total'] = $order_data['total_amount'];
$_SESSION['order_id'] = $order_id;

// Redirect to order confirmation
header("Location: order_confirmation.php");
exit();

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();
        
        // Create orders table if not exists
        $orders_table = $conn->query("SHOW TABLES LIKE 'orders'")->fetch();
        if (!$orders_table) {
            $conn->exec("
                CREATE TABLE orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    order_number VARCHAR(50) NOT NULL UNIQUE,
                    total_amount DECIMAL(10,2) NOT NULL,
                    subtotal DECIMAL(10,2) NOT NULL,
                    tax_amount DECIMAL(10,2) NOT NULL,
                    shipping_amount DECIMAL(10,2) NOT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    shipping_address TEXT NOT NULL,
                    payment_method VARCHAR(100) NOT NULL,
                    payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        }

        // Create order_items table if not exists
        $order_items_table = $conn->query("SHOW TABLES LIKE 'order_items'")->fetch();
        if (!$order_items_table) {
            $conn->exec("
                CREATE TABLE order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id INT NOT NULL,
                    product_name VARCHAR(255) NOT NULL,
                    product_image VARCHAR(500) NOT NULL,
                    quantity INT NOT NULL,
                    unit_price DECIMAL(10,2) NOT NULL,
                    total_price DECIMAL(10,2) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )
            ");
        }
        
        // Insert order
        $order_stmt = $conn->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, subtotal, tax_amount, shipping_amount, status, shipping_address, payment_method, payment_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $payment_method = $_POST['payment_method'];
        $payment_status = 'paid'; // Assuming payment is successful
        
        $order_stmt->execute([
            $order_data['user_id'],
            $order_data['order_number'],
            $order_data['total_amount'],
            $order_data['subtotal'],
            $order_data['tax_amount'],
            $order_data['shipping_amount'],
            'confirmed',
            $order_data['shipping_address'],
            $payment_method,
            $payment_status
        ]);
        
        $order_id = $conn->lastInsertId();
        
        // Insert order items
        foreach ($cart_items as $item) {
            $order_item_stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $order_item_stmt->execute([
                $order_id,
                $item['product_id'],
                $item['product_name'],
                $item['product_image'],
                $item['quantity'],
                $item['product_price'],
                $item['total_price']
            ]);
        }
        
        // Clear cart
        $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_cart_stmt->execute([$_SESSION['user_id']]);
        
        // Clear pending order from session
        unset($_SESSION['pending_order']);
        
        $conn->commit();
        
        // Redirect to order confirmation
        $_SESSION['success_message'] = "🎉 Payment successful! Your order has been placed. Order number: {$order_data['order_number']}";
        header("Location: order_details.php?order_id=$order_id");
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Payment failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1000px;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .payment-method.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="payment-container">
            <!-- Payment Header -->
            <div class="payment-header">
                <h1><i class="fas fa-credit-card me-2"></i>Payment</h1>
                <p class="mb-0">Complete your order #<?php echo $order_data['order_number']; ?></p>
            </div>

            <div class="p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <form method="POST" id="paymentForm">
                            <h3 class="mb-4">Payment Method</h3>
                            
                            <div class="payment-methods">
                                <div class="payment-method" onclick="selectPaymentMethod('credit_card')">
                                    <div class="payment-icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <h5>Credit Card</h5>
                                    <p class="small mb-0">Pay with your credit card</p>
                                    <input type="radio" name="payment_method" value="Credit Card" style="display: none;">
                                </div>
                                
                                <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                                    <div class="payment-icon">
                                        <i class="fab fa-paypal"></i>
                                    </div>
                                    <h5>PayPal</h5>
                                    <p class="small mb-0">Pay with your PayPal account</p>
                                    <input type="radio" name="payment_method" value="PayPal" style="display: none;">
                                </div>
                                
                                <div class="payment-method" onclick="selectPaymentMethod('stripe')">
                                    <div class="payment-icon">
                                        <i class="fab fa-cc-stripe"></i>
                                    </div>
                                    <h5>Stripe</h5>
                                    <p class="small mb-0">Secure payment with Stripe</p>
                                    <input type="radio" name="payment_method" value="Stripe" style="display: none;">
                                </div>
                                
                                <div class="payment-method" onclick="selectPaymentMethod('bank_transfer')">
                                    <div class="payment-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <h5>Bank Transfer</h5>
                                    <p class="small mb-0">Transfer directly to our bank</p>
                                    <input type="radio" name="payment_method" value="Bank Transfer" style="display: none;">
                                </div>
                            </div>

                            <!-- Credit Card Form (shown when credit card is selected) -->
                            <div id="creditCardForm" style="display: none;">
                                <h5 class="mt-4 mb-3">Credit Card Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Card Number</label>
                                            <input type="text" class="form-control" placeholder="1234 5678 9012 3456" name="card_number">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="text" class="form-control" placeholder="MM/YY" name="expiry_date">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">CVV</label>
                                            <input type="text" class="form-control" placeholder="123" name="cvv">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Cardholder Name</label>
                                            <input type="text" class="form-control" placeholder="John Doe" name="cardholder_name">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-lock me-2"></i>Pay $<?= number_format($order_data['total_amount'], 2) ?>
                                </button>
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Cart
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="order-summary">
                            <h4 class="mb-3">Order Summary</h4>
                            
                            <!-- Order Items -->
                            <?php foreach($cart_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <small class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></small>
                                        <br>
                                        <small class="text-muted">Qty: <?= $item['quantity'] ?> × $<?= number_format($item['product_price'], 2) ?></small>
                                    </div>
                                    <small class="fw-bold">$<?= number_format($item['total_price'], 2) ?></small>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Subtotal:</span>
                                <span>$<?= number_format($order_data['subtotal'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Tax (8%):</span>
                                <span>$<?= number_format($order_data['tax_amount'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Shipping:</span>
                                <span><?= $order_data['shipping_amount'] == 0 ? 'FREE' : '$' . number_format($order_data['shipping_amount'], 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span class="text-success">$<?= number_format($order_data['total_amount'], 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="order-summary">
                            <h5 class="mb-3">Shipping Address</h5>
                            <p class="small mb-0"><?= nl2br(htmlspecialchars($order_data['shipping_address'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectPaymentMethod(method) {
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
                el.querySelector('input[type="radio"]').checked = false;
            });
            
            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');
            event.currentTarget.querySelector('input[type="radio"]').checked = true;
            
            // Show/hide credit card form
            const creditCardForm = document.getElementById('creditCardForm');
            if (method === 'credit_card') {
                creditCardForm.style.display = 'block';
            } else {
                creditCardForm.style.display = 'none';
            }
        }
        
        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return false;
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>