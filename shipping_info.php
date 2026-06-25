<?php
session_start();
require_once 'config.php';

// Check if user is logged in and has cart items
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user's existing information
try {
    $stmt = $conn->prepare("SELECT first_name, last_name, email, phone, address FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $user_info = [];
}

// Get cart items to show order summary
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
    $shipping_amount = $subtotal > 100 ? 0 : 9.99;
    $total_amount = $subtotal + $tax_amount + $shipping_amount;
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'country'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Update user information in database
        $update_stmt = $conn->prepare("
            UPDATE users SET 
            first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, 
            city = ?, state = ?, zip_code = ?, country = ?
            WHERE id = ?
        ");
        
        $update_stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['city'],
            $_POST['state'],
            $_POST['zip_code'],
            $_POST['country'],
            $user_id
        ]);
        
        // Store shipping information in session for payment page
        $_SESSION['shipping_info'] = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'zip_code' => $_POST['zip_code'],
            'country' => $_POST['country']
        ];
        
        // Redirect to payment page
        header("Location: payment.php");
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Information - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .shipping-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1200px;
        }
        
        .shipping-header {
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
        
        .form-section {
            padding: 30px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="shipping-container">
            <!-- Shipping Header -->
            <div class="shipping-header">
                <h1><i class="fas fa-shipping-fast me-2"></i>Shipping Information</h1>
                <p class="mb-0">Please provide your shipping details to continue</p>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="form-section">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" id="shippingForm">
                            <h3 class="mb-4">Contact Information</h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?= htmlspecialchars($user_info['first_name'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?= htmlspecialchars($user_info['last_name'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>

                            <h3 class="mb-4 mt-4">Shipping Address</h3>
                            
                            <div class="mb-3">
                                <label class="form-label">Street Address *</label>
                                <input type="text" class="form-control" name="address" 
                                       value="<?= htmlspecialchars($user_info['address'] ?? '') ?>" 
                                       placeholder="123 Main St" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">City *</label>
                                        <input type="text" class="form-control" name="city" 
                                               value="<?= htmlspecialchars($user_info['city'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">State *</label>
                                        <input type="text" class="form-control" name="state" 
                                               value="<?= htmlspecialchars($user_info['state'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">ZIP Code *</label>
                                        <input type="text" class="form-control" name="zip_code" 
                                               value="<?= htmlspecialchars($user_info['zip_code'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Country *</label>
                                <select class="form-control" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="United States" <?= (($user_info['country'] ?? '') == 'United States') ? 'selected' : '' ?>>United States</option>
                                    <option value="Canada" <?= (($user_info['country'] ?? '') == 'Canada') ? 'selected' : '' ?>>Canada</option>
                                    <option value="United Kingdom" <?= (($user_info['country'] ?? '') == 'United Kingdom') ? 'selected' : '' ?>>United Kingdom</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Delivery Instructions (Optional)</label>
                                <textarea class="form-control" name="delivery_instructions" rows="3" 
                                          placeholder="Any special delivery instructions..."></textarea>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Continue to Payment
                                </button>
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Cart
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="p-4">
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
                                <span>$<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Tax (8%):</span>
                                <span>$<?= number_format($tax_amount, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Shipping:</span>
                                <span><?= $shipping_amount == 0 ? 'FREE' : '$' . number_format($shipping_amount, 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span class="text-success">$<?= number_format($total_amount, 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-lock me-1"></i>Your information is secure and encrypted
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>