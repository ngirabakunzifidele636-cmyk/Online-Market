<?php
session_start();
require_once 'config.php';
require_once 'email_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Safe user info retrieval
try {
    // Get only basic user info that definitely exists
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone, address FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_info) {
        throw new Exception("User not found.");
    }
    
    // Set default values for optional fields
    $user_info['city'] = '';
    $user_info['state'] = '';
    $user_info['zip_code'] = '';
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

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
    $tax_rate = 0.08;
    $tax_amount = $subtotal * $tax_rate;
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
        
        // Validate payment method
        if (empty($_POST['payment_method'])) {
            throw new Exception("Please select a payment method.");
        }

        // Update only basic user info
        $update_stmt = $conn->prepare("
            UPDATE users SET 
            first_name = ?, last_name = ?, email = ?, phone = ?, address = ?
            WHERE id = ?
        ");
        
        $update_stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'] ?? '',
            $_POST['address'],
            $user_id
        ]);

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

        // Get payment method
        $payment_method = $_POST['payment_method'];
        $payment_status = 'pending';
        
        // Validate and set mobile money number
        if ($payment_method == 'mobile_momo') {
            if (empty($_POST['momo_number'])) {
                throw new Exception("Please enter your MTN MoMo number.");
            }
            $mobile_number = preg_replace('/[^0-9]/', '', $_POST['momo_number']);
            $mobile_provider = 'MoMo';
            
            // Validate MTN MoMo format (starts with 78 or 79)
            if (!preg_match('/^[7][89][0-9]{7}$/', $mobile_number)) {
                throw new Exception("Please enter a valid MTN MoMo number (starts with 78 or 79, e.g., 78XXXXXXX).");
            }
        } elseif ($payment_method == 'mobile_airtel') {
            if (empty($_POST['airtel_number'])) {
                throw new Exception("Please enter your Airtel Money number.");
            }
            $mobile_number = preg_replace('/[^0-9]/', '', $_POST['airtel_number']);
            $mobile_provider = 'Airtel Money';
            
            // Validate Airtel Money format (starts with 72 or 73)
            if (!preg_match('/^[7][23][0-9]{7}$/', $mobile_number)) {
                throw new Exception("Please enter a valid Airtel Money number (starts with 72 or 73, e.g., 72XXXXXXX).");
            }
        } else {
            // Validate credit card info
            if (empty($_POST['card_number']) || empty($_POST['expiry_date']) || empty($_POST['cvv']) || empty($_POST['card_name'])) {
                throw new Exception("Please fill in all credit card details.");
            }
            $mobile_provider = null;
            $mobile_number = null;
        }

        // First, check if mobile money columns exist and add them if they don't
        try {
            $check_momo_number = $conn->query("SHOW COLUMNS FROM orders LIKE 'mobile_money_number'")->fetch();
            if (!$check_momo_number) {
                $conn->exec("ALTER TABLE orders ADD COLUMN mobile_money_number VARCHAR(20) NULL AFTER payment_status");
            }
            
            $check_momo_provider = $conn->query("SHOW COLUMNS FROM orders LIKE 'mobile_money_provider'")->fetch();
            if (!$check_momo_provider) {
                $conn->exec("ALTER TABLE orders ADD COLUMN mobile_money_provider VARCHAR(50) NULL AFTER mobile_money_number");
            }
        } catch (PDOException $e) {
            // Columns might already exist, continue
        }

                // Create order with appropriate fields
        if ($payment_method == 'mobile_momo' || $payment_method == 'mobile_airtel') {
            // Mobile money order
            $order_stmt = $conn->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, subtotal, tax_amount, shipping_amount, status, shipping_address, payment_method, payment_status, mobile_money_number, mobile_money_provider) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $order_stmt->execute([
                $user_id,
                $order_number,
                $total_amount,
                $subtotal,
                $tax_amount,
                $shipping_amount,
                'pending',
                $shipping_address,
                $mobile_provider,
                $payment_status,
                $mobile_number,
                $mobile_provider
            ]);
        } else {
            // Credit card order
            $order_stmt = $conn->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, subtotal, tax_amount, shipping_amount, status, shipping_address, payment_method, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $order_stmt->execute([
                $user_id,
                $order_number,
                $total_amount,
                $subtotal,
                $tax_amount,
                $shipping_amount,
                'confirmed',
                $shipping_address,
                'Credit Card',
                'paid'
            ]);
        }
        
        $order_id = $conn->lastInsertId();
        
        // Insert order items
        foreach ($cart_items as $item) {
            try {
                $order_item_stmt = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, unit_price, total_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $order_item_stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_image'] ?? '',
                    $item['quantity'],
                    $item['product_price'],
                    $item['total_price']
                ]);
            } catch(PDOException $e) {
                // Fallback without product_image
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
        }
        
        // Clear cart
        $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_cart_stmt->execute([$user_id]);
        
    
        try {
            // Create notifications table if it doesn't exist
            $conn->exec("
                CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    type VARCHAR(50) DEFAULT 'info',
                    icon VARCHAR(50) DEFAULT 'fa-bell',
                    category VARCHAR(50) DEFAULT 'system',
                    is_read TINYINT DEFAULT 0,
                    action_url VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_read (user_id, is_read)
                )
            ");

            // Insert notification for order placed
            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $status_text = ($payment_method == 'mobile_momo' || $payment_method == 'mobile_airtel') ? 'pending payment' : 'confirmed';
            $action_url = 'orders.php';
            
            // Order confirmation notification
            $notif_stmt->execute([
                $user_id,
                '✅ Order Placed Successfully',
                'Your order #' . $order_number . ' has been placed and is ' . $status_text . '. Total: $' . number_format($total_amount, 2),
                'success',
                'fa-shopping-bag',
                'order',
                $action_url
            ]);

            // Payment notification for mobile money
            if ($payment_method == 'mobile_momo' || $payment_method == 'mobile_airtel') {
                $notif_stmt->execute([
                    $user_id,
                    '💳 Payment Required',
                    'Please complete your payment of $' . number_format($total_amount, 2) . ' for order #' . $order_number . ' using ' . $mobile_provider . '. Click to view payment instructions.',
                    'warning',
                    'fa-credit-card',
                    'payment',
                    'payment_instructions.php?order_id=' . $order_id
                ]);
            } else {
                // Payment success notification for credit card
                $notif_stmt->execute([
                    $user_id,
                    '💰 Payment Successful',
                    'Your payment of $' . number_format($total_amount, 2) . ' for order #' . $order_number . ' has been processed successfully.',
                    'success',
                    'fa-check-circle',
                    'payment',
                    $action_url
                ]);
            }
            
            // Shipping estimate notification
            $shipping_date = date('Y-m-d', strtotime('+3 days'));
            $notif_stmt->execute([
                $user_id,
                '🚚 Shipping Estimate',
                'Your order #' . $order_number . ' is estimated to ship by ' . $shipping_date . '. You will receive updates when it ships.',
                'info',
                'fa-truck',
                'order',
                $action_url
            ]);
            
        } catch (PDOException $e) {
            // Log error but don't stop the order process
            error_log("Failed to create order notifications: " . $e->getMessage());
        }
        

        // Prepare order details for email
        $order_details = [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'total_amount' => $total_amount,
            'subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'shipping_amount' => $shipping_amount,
            'shipping_address' => $shipping_address,
            'payment_method' => $mobile_provider ?? 'Credit Card',
            'payment_status' => $payment_status,
            'mobile_money_number' => $mobile_number ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Prepare order items for email
        $order_items_for_email = [];
        foreach ($cart_items as $item) {
            $order_items_for_email[] = [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'product_price' => $item['product_price'],
                'total_price' => $item['total_price']
            ];
        }

        // Send order confirmation email to customer
        $customer_email_content = EmailTemplates::orderConfirmation($order_details, $order_items_for_email, $user_info);
        $customer_email_sent = EmailConfig::sendEmail(
            $user_info['email'],
            "✅ Order Confirmation - " . $order_number,
            $customer_email_content
        );

        // Send admin notification email
        $admin_email = "admin@techshop.com"; // Change this to your actual admin email
        $admin_subject = "🛍️ New Order Received - " . $order_number;
        
        // Use the admin notification template
        $admin_email_content = EmailTemplates::adminNewOrderNotification(
            $order_details, 
            $order_items_for_email, 
            $user_info, 
            $mobile_provider ?? 'Credit Card',
            $mobile_number ?? null
        );
        
        $admin_email_sent = EmailConfig::sendEmail(
            $admin_email,
            $admin_subject,
            $admin_email_content
        );

        // Log email status
        if (!$customer_email_sent || !$admin_email_sent) {
            error_log("Email sending issue - Customer: " . ($customer_email_sent ? 'OK' : 'Failed') . 
                      ", Admin: " . ($admin_email_sent ? 'OK' : 'Failed') . " for order: " . $order_number);
        }

        // Set session data for order confirmation
        $_SESSION['order_success'] = true;
        $_SESSION['order_number'] = $order_number;
        $_SESSION['order_total'] = $total_amount;
        $_SESSION['order_id'] = $order_id;
        $_SESSION['email_sent'] = $customer_email_sent;

        // Redirect based on payment method
        if ($payment_method == 'mobile_momo' || $payment_method == 'mobile_airtel') {
            $_SESSION['payment_method'] = $mobile_provider;
            $_SESSION['mobile_number'] = $mobile_number;
            header("Location: payment_instructions.php?order_id=" . $order_id);
        } else {
            header("Location: order_confirmation.php");
        }
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
    <title>Checkout - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .checkout-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1200px;
        }
        
        .checkout-header {
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
        
        .payment-method-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method-card:hover {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        .payment-method-card.selected {
            border-color: #28a745;
            background-color: #f0fff0;
        }
        .payment-method-card input[type="radio"] {
            margin-right: 10px;
        }
        .payment-logo {
            width: 30px;
            height: 30px;
            object-fit: contain;
            margin-right: 10px;
        }
        .mobile-money-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #28a745;
        }
        .credit-card-section {
            display: none;
        }
        .momo-input, .airtel-input {
            display: none;
        }
        .provider-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }
        .badge-mtn {
            background-color: #ffc107;
            color: #000;
        }
        .badge-airtel {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="checkout-container">
            <!-- Checkout Header -->
            <div class="checkout-header">
                <h1><i class="fas fa-credit-card me-2"></i>Checkout</h1>
                <p class="mb-0">Complete your purchase securely</p>
            </div>

            <div class="p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <?php if (empty($cart_items)): ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-3"></i>
                        <h4>Your cart is empty</h4>
                        <p>Add some products to your cart before checking out.</p>
                        <a href="products.php" class="btn btn-primary">Browse Products</a>
                    </div>
                <?php else: ?>
                    <form method="POST" id="checkoutForm" onsubmit="return validateForm()">
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Shipping Information -->
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Shipping Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">First Name *</label>
                                                <input type="text" class="form-control" name="first_name" 
                                                       value="<?= htmlspecialchars($user_info['first_name'] ?? '') ?>" 
                                                       required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Last Name *</label>
                                                <input type="text" class="form-control" name="last_name"
                                                       value="<?= htmlspecialchars($user_info['last_name'] ?? '') ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Email Address *</label>
                                            <input type="email" class="form-control" name="email"
                                                   value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" 
                                                   required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone"
                                                   value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>" 
                                                   placeholder="+250 78 123 4567">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Street Address *</label>
                                            <input type="text" class="form-control" name="address"
                                                   value="<?= htmlspecialchars($user_info['address'] ?? '') ?>" 
                                                   placeholder="123 Main Street" required>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">City *</label>
                                                <input type="text" class="form-control" name="city"
                                                       value="<?= htmlspecialchars($user_info['city'] ?? '') ?>" 
                                                       required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">State/Province *</label>
                                                <select class="form-select" name="state" required>
                                                    <option value="">Choose...</option>
                                                    <option value="Kigali" <?= (($user_info['state'] ?? '') == 'Kigali') ? 'selected' : '' ?>>Kigali</option>
                                                    <option value="Masanze" <?= (($user_info['state'] ?? '') == 'Masanze') ? 'selected' : '' ?>>Masanze</option>
                                                    <option value="Rusizi" <?= (($user_info['state'] ?? '') == 'Rusizi') ? 'selected' : '' ?>>Rusizi</option>
                                                    <option value="Rubavu" <?= (($user_info['state'] ?? '') == 'Rubavu') ? 'selected' : '' ?>>Rubavu</option>
                                                    <option value="Nyagatare" <?= (($user_info['state'] ?? '') == 'Nyagatare') ? 'selected' : '' ?>>Nyagatare</option>
                                                    <option value="Kayonza" <?= (($user_info['state'] ?? '') == 'Kayonza') ? 'selected' : '' ?>>Kayonza</option>
                                                    <option value="Gasabo" <?= (($user_info['state'] ?? '') == 'Gasabo') ? 'selected' : '' ?>>Gasabo</option>
                                                    <option value="Kicukiro" <?= (($user_info['state'] ?? '') == 'Kicukiro') ? 'selected' : '' ?>>Kicukiro</option>
                                                    <option value="Nyarungege" <?= (($user_info['state'] ?? '') == 'Nyarungege') ? 'selected' : '' ?>>Nyarungege</option>
                                                    <option value="Rwamagana" <?= (($user_info['state'] ?? '') == 'Rwamagana') ? 'selected' : '' ?>>Rwamagana</option>
                                                    <option value="Gatsibo" <?= (($user_info['state'] ?? '') == 'Gatsibo') ? 'selected' : '' ?>>Gatsibo</option>
                                                    <option value="Kampla" <?= (($user_info['state'] ?? '') == 'Kampla') ? 'selected' : '' ?>>Kampla</option>
                                                    <option value="Bujumura" <?= (($user_info['state'] ?? '') == 'Bujumura') ? 'selected' : '' ?>>Bujumura</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">ZIP Code *</label>
                                                <input type="text" class="form-control" name="zip_code"
                                                       value="<?= htmlspecialchars($user_info['zip_code'] ?? '') ?>" 
                                                       required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Information -->
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Payment Method Selection -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Select Payment Method *</label>
                                            
                                            <!-- Credit Card Option -->
                                            <div class="payment-method-card" onclick="selectPayment('credit_card')">
                                                <div class="d-flex align-items-center">
                                                    <input type="radio" name="payment_method" id="credit_card" value="credit_card" class="me-2">
                                                    <i class="fas fa-credit-card fa-2x me-3 text-primary"></i>
                                                    <div>
                                                        <strong>Credit Card</strong>
                                                        <small class="text-muted d-block">Pay with Visa, Mastercard, etc.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- MoMo (MTN Mobile Money) Option -->
                                            <div class="payment-method-card" onclick="selectPayment('mobile_momo')">
                                                <div class="d-flex align-items-center">
                                                    <input type="radio" name="payment_method" id="mobile_momo" value="mobile_momo" class="me-2">
                                                    <i class="fas fa-mobile-alt fa-2x me-3 text-warning"></i>
                                                    <div>
                                                        <strong>MoMo (MTN Mobile Money)</strong>
                                                        <!-- <span class="provider-badge badge-mtn">78/79</span> -->
                                                        <small class="text-muted d-block">Pay with your MTN mobile money</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Airtel Money Option -->
                                            <div class="payment-method-card" onclick="selectPayment('mobile_airtel')">
                                                <div class="d-flex align-items-center">
                                                    <input type="radio" name="payment_method" id="mobile_airtel" value="mobile_airtel" class="me-2">
                                                    <i class="fas fa-mobile-alt fa-2x me-3 text-danger"></i>
                                                    <div>
                                                        <strong>Airtel Money</strong>
                                                        <!-- <span class="provider-badge badge-airtel">72/73</span> -->
                                                        <small class="text-muted d-block">Pay with your Airtel mobile money</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Credit Card Details Section -->
                                        <div id="creditCardSection" class="credit-card-section">
                                            <div class="mb-3">
                                                <label class="form-label">Card Number *</label>
                                                <input type="text" class="form-control" name="card_number" 
                                                       placeholder="1234 5678 9012 3456">
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Expiry Date *</label>
                                                        <input type="text" class="form-control" name="expiry_date" 
                                                               placeholder="MM/YY">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">CVV *</label>
                                                        <input type="text" class="form-control" name="cvv" 
                                                               placeholder="123">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Name on Card *</label>
                                                <input type="text" class="form-control" name="card_name" 
                                                       value="<?= htmlspecialchars(($user_info['first_name'] ?? '') . ' ' . ($user_info['last_name'] ?? '')) ?>">
                                            </div>
                                        </div>
                                        
                                        <!-- Mobile Money Details Section -->
                                        <div id="mobileMoneySection" class="mobile-money-section">
                                            <!-- MTN MoMo Input -->
                                            <div id="momoInput" class="momo-input">
                                                <label class="form-label fw-bold text-warning">
                                                    <i class="fas fa-mobile-alt me-2"></i>MTN MoMo Number
                                                    <!-- <span class="provider-badge badge-mtn">78 or 79</span> -->
                                                </label>
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text">+250</span>
                                                    <input type="tel" 
                                                           class="form-control" 
                                                           id="momo_number" 
                                                           name="momo_number" 
                                                           placeholder="78XXXXXXX or 79XXXXXXX"
                                                           pattern="[0-9]{9}"
                                                           maxlength="9">
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Enter your MTN MoMo number (e.g., 78XXXXXXX)
                                                </small>
                                            </div>
                                            
                                            <!-- Airtel Money Input -->
                                            <div id="airtelInput" class="airtel-input">
                                                <label class="form-label fw-bold text-danger">
                                                    <i class="fas fa-mobile-alt me-2"></i>Airtel Money Number
                                                    <!-- <span class="provider-badge badge-airtel">72 or 73</span> -->
                                                </label>
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text">+250</span>
                                                    <input type="tel" 
                                                           class="form-control" 
                                                           id="airtel_number" 
                                                           name="airtel_number" 
                                                           placeholder="72XXXXXXX or 73XXXXXXX"
                                                           pattern="[0-9]{9}"
                                                           maxlength="9">
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Enter your Airtel Money number (e.g., 72XXXXXXX)
                                                </small>
                                            </div>
                                            
                                            <div class="mt-3 alert alert-info p-2 small">
                                                <i class="fas fa-clock me-1"></i>
                                                You will receive payment instructions after placing your order.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <!-- Order Summary -->
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($cart_items as $item): ?>
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
                                        <div class="d-flex justify-content-between fw-bold fs-5">
                                            <span>Total:</span>
                                            <span class="text-success">$<?= number_format($total_amount, 2) ?></span>
                                        </div>
                                        
                                        <!-- Payment Method Icons -->
                                        <div class="text-center my-3">
                                            <small class="text-muted d-block mb-2">We accept:</small>
                                            <div class="d-flex justify-content-center gap-3">
                                                <i class="fab fa-cc-visa fa-2x text-primary" title="Visa"></i>
                                                <i class="fab fa-cc-mastercard fa-2x text-danger" title="Mastercard"></i>
                                                <i class="fas fa-mobile-alt fa-2x text-warning" title="MTN MoMo (78/79)"></i>
                                                <i class="fas fa-mobile-alt fa-2x text-danger" title="Airtel Money (72/73)"></i>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success w-100 mt-3 btn-lg">
                                            <i class="fas fa-lock me-2"></i>Place Order
                                        </button>
                                        
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-lock me-1"></i> Secure SSL encrypted payment
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function selectPayment(method) {
            // Uncheck all radio buttons
            document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                radio.checked = false;
            });
            
            // Check the selected radio
            document.getElementById(method).checked = true;
            
            // Remove selected class from all cards
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to parent card
            event.currentTarget.classList.add('selected');
            
            // Hide all payment sections
            document.getElementById('creditCardSection').style.display = 'none';
            document.getElementById('mobileMoneySection').style.display = 'none';
            document.getElementById('momoInput').style.display = 'none';
            document.getElementById('airtelInput').style.display = 'none';
            
            // Remove required attributes from all payment fields
            document.querySelectorAll('[name="card_number"], [name="expiry_date"], [name="cvv"], [name="card_name"], [name="momo_number"], [name="airtel_number"]').forEach(field => {
                field.required = false;
            });
            
            // Show relevant section and set required fields
            if (method === 'credit_card') {
                document.getElementById('creditCardSection').style.display = 'block';
                document.querySelectorAll('[name="card_number"], [name="expiry_date"], [name="cvv"], [name="card_name"]').forEach(field => {
                    field.required = true;
                });
            } else if (method === 'mobile_momo') {
                document.getElementById('mobileMoneySection').style.display = 'block';
                document.getElementById('momoInput').style.display = 'block';
                document.querySelector('[name="momo_number"]').required = true;
            } else if (method === 'mobile_airtel') {
                document.getElementById('mobileMoneySection').style.display = 'block';
                document.getElementById('airtelInput').style.display = 'block';
                document.querySelector('[name="airtel_number"]').required = true;
            }
        }
        
        function validateForm() {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!paymentMethod) {
                alert('Please select a payment method');
                return false;
            }
            
            // Validate mobile money numbers with correct prefixes
            if (paymentMethod.value === 'mobile_momo') {
                const momoNumber = document.getElementById('momo_number').value.trim();
                if (!momoNumber) {
                    alert('Please enter your MTN MoMo number');
                    return false;
                }
                
                // Validate MTN MoMo number (starts with 78 or 79)
                const momoRegex = /^[7][89][0-9]{7}$/;
                if (!momoRegex.test(momoNumber)) {
                    alert('Please enter a valid MTN MoMo number (must start with 78 or 79, e.g., 78XXXXXXX)');
                    return false;
                }
            } else if (paymentMethod.value === 'mobile_airtel') {
                const airtelNumber = document.getElementById('airtel_number').value.trim();
                if (!airtelNumber) {
                    alert('Please enter your Airtel Money number');
                    return false;
                }
                
                // Validate Airtel Money number (starts with 72 or 73)
                const airtelRegex = /^[7][23][0-9]{7}$/;
                if (!airtelRegex.test(airtelNumber)) {
                    alert('Please enter a valid Airtel Money number (must start with 72 or 73, e.g., 72XXXXXXX)');
                    return false;
                }
            }
            
            return true;
        }
    </script>
</body>
</html>

<?php include 'footer.php'; ?>