<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        // Update quantities
        foreach ($_POST['quantities'] as $cart_id => $quantity) {
            if ($quantity <= 0) {
                // Remove item if quantity is 0
                $delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $delete_stmt->execute([$cart_id, $user_id]);
            } else {
                // Update quantity
                $update_stmt = $conn->prepare("
                    UPDATE cart 
                    SET quantity = ?, total_price = product_price * ? 
                    WHERE id = ? AND user_id = ?
                ");
                $update_stmt->execute([$quantity, $quantity, $cart_id, $user_id]);
            }
        }
        $success = "Cart updated successfully!";
        
    } elseif (isset($_POST['remove_item'])) {
        // Remove single item
        $cart_id = $_POST['cart_id'];
        $delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $delete_stmt->execute([$cart_id, $user_id]);
        $success = "Item removed from cart!";
        
    } elseif (isset($_POST['checkout'])) {
        // Create order from cart
        try {
            $conn->beginTransaction();
            
            // Get cart items
            $cart_stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
            $cart_stmt->execute([$user_id]);
            $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($cart_items)) {
                throw new Exception("Your cart is empty!");
            }
            
            // Calculate totals
            $subtotal = 0;
            foreach ($cart_items as $item) {
                $subtotal += $item['total_price'];
            }
            $tax_amount = $subtotal * 0.08; // 8% tax
            $shipping_amount = $subtotal > 50 ? 0 : 9.99; // Free shipping over $50
            $total_amount = $subtotal + $tax_amount + $shipping_amount;
            
            // Generate order number
            $order_number = 'ORD' . date('YmdHis') . rand(100, 999);
            
            // Create order
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
                'pending',
                '123 Main St, City, State 12345', // Default address
                'Credit Card',
                'pending'
            ]);
            
            $order_id = $conn->lastInsertId();
            
            // Add order items
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
            
            $conn->commit();
            
            $success = "Order created successfully! Order #: $order_number";
            $_SESSION['success_message'] = $success;
            header("Location: order_details.php?order_id=$order_id");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Checkout failed: " . $e->getMessage();
        }
    }
}

// Get cart items
try {
    $cart_stmt = $conn->prepare("
        SELECT c.*, p.description 
        FROM cart c 
        LEFT JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $cart_stmt->execute([$user_id]);
    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['total_price'];
    }
    $tax_amount = $subtotal * 0.08;
    $shipping_amount = $subtotal > 50 ? 0 : 9.99;
    $total_amount = $subtotal + $tax_amount + $shipping_amount;
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
            <div class="row">
                <div class="col-md-8">
                    <form method="POST">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Cart Items (<?= count($cart_items) ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="row align-items-center mb-3 pb-3 border-bottom">
                                        <div class="col-md-2">
                                            <img src="<?= getProductImage($item['product_name']) ?>" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                 class="img-fluid rounded">
                                        </div>
                                        <div class="col-md-4">
                                            <h6><?= htmlspecialchars($item['product_name']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($item['description'] ?? '') ?></small>
                                            <div class="mt-2">
                                                <strong class="text-primary">$<?= number_format($item['product_price'], 2) ?></strong> each
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" 
                                                   name="quantities[<?= $item['id'] ?>]" 
                                                   value="<?= $item['quantity'] ?>" 
                                                   min="0" 
                                                   max="10" 
                                                   class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <strong>$<?= number_format($item['total_price'], 2) ?></strong>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="submit" name="remove_item" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_cart" class="btn btn-outline-primary">
                                        <i class="fas fa-sync me-2"></i>Update Cart
                                    </button>
                                    <a href="products.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (8%):</span>
                                <span>$<?= number_format($tax_amount, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span><?= $shipping_amount == 0 ? 'FREE' : '$' . number_format($shipping_amount, 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong>$<?= number_format($total_amount, 2) ?></strong>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="checkout" class="btn btn-success w-100 btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h3>Your Cart is Empty</h3>
                <p class="text-muted mb-4">Add some products to your cart to see them here.</p>
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Function to get product image
function getProductImage($product_name) {
    // Same function as in products_fixed.php
    $product_images = [
        'wireless headphones' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'headphones' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'bluetooth' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'smartphone' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=300&fit=crop',
        'phone case' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop',
        'case' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop',
        'laptop' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=300&fit=crop',
        'sleeve' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=300&fit=crop',
        'usb' => 'https://images.unsplash.com/photo-1580522154071-c6ca47a859ad?w=400&h=300&fit=crop',
        'cable' => 'https://images.unsplash.com/photo-1580522154071-c6ca47a859ad?w=400&h=300&fit=crop',
        'screen protector' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop',
        'protector' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop'
    ];
    
    $product_name_lower = strtolower($product_name);
    
    foreach ($product_images as $key => $image) {
        if (strpos($product_name_lower, $key) !== false) {
            return $image;
        }
    }
    
    $fallback_images = [
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=300&fit=crop'
    ];
    
    return $fallback_images[array_rand($fallback_images)];
}
?>