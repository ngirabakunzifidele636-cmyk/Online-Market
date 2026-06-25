<?php

include 'config.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
 elseif (isset($_POST['checkout'])) {
    // Redirect to shipping information page first
    header("Location: checkout.php");
    exit();
}


// Ensure cart table has product_image column
try {
    $check_column = $conn->query("SHOW COLUMNS FROM cart LIKE 'product_image'")->fetch();
    if (!$check_column) {
        $conn->exec("ALTER TABLE cart ADD COLUMN product_image VARCHAR(500) NOT NULL AFTER product_price");
    }
} catch (PDOException $e) {
    // Continue if error
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $cart_id => $quantity) {
            if ($quantity <= 0) {
                $delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $delete_stmt->execute([$cart_id, $user_id]);
            } else {
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
        $cart_id = $_POST['cart_id'];
        $delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $delete_stmt->execute([$cart_id, $user_id]);
        $success = "Item removed from cart!";
        
    } elseif (isset($_POST['checkout'])) {
               try {
            $conn->beginTransaction();
            
          
            $cart_stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
            $cart_stmt->execute([$user_id]);
            $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($cart_items)) {
                throw new Exception("Your cart is empty! Add some products first.");
            }
            
          
            $subtotal = 0;
            foreach ($cart_items as $item) {
                $subtotal += $item['total_price'];
            }
            $tax_amount = $subtotal * 0.08; 
            $shipping_amount = $subtotal > 100 ? 0 : 9.99; 
            $total_amount = $subtotal + $tax_amount + $shipping_amount;
            
            
            $order_number = 'CUST' . date('YmdHis') . rand(100, 999);
            
            
            $user_stmt = $conn->prepare("SELECT first_name, last_name, email, phone, address FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            
            $shipping_address = "{$user_info['first_name']} {$user_info['last_name']}\n";
            $shipping_address .= "{$user_info['address']}\n";
            $shipping_address .= "Phone: {$user_info['phone']}\n";
            $shipping_address .= "Email: {$user_info['email']}";
            
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
                        quantity INT NOT NULL,
                        unit_price DECIMAL(10,2) NOT NULL,
                        total_price DECIMAL(10,2) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                    )
                ");
            }
            
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
                $shipping_address,
                'Credit Card',
                'pending'
            ]);
            
            $order_id = $conn->lastInsertId();
            
            
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
            
            
            $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_cart_stmt->execute([$user_id]);
            
            $conn->commit();
            
            
            $_SESSION['success_message'] = "🎉 Order created successfully! Your order number is: $order_number";
            header("Location: shipping_info.php?order_id=$order_id");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Checkout failed: " . $e->getMessage();
        }
    }
}


 // Get cart items
 try {
    $cart_stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
   $cart_stmt->execute([$user_id]);
  $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
   $subtotal = 0;
   foreach ($cart_items as $item) {
       $subtotal += $item['total_price'];
    }
    $tax_amount = $subtotal * 0.08;
    $shipping_amount = $subtotal > 100 ? 0 : 9.99;
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
        <h1 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Your Shopping Cart</h1>

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
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-shopping-basket me-2"></i>Cart Items (<?= count($cart_items) ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="row align-items-center mb-3 pb-3 border-bottom">
                                        <div class="col-md-2">
                                            <img src="<?= $item['product_image'] ?? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=150&h=150&fit=crop' ?>" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                 class="img-fluid rounded" style="height: 80px; object-fit: cover;">
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                                            <small class="text-muted">Unit Price: $<?= number_format($item['product_price'], 2) ?></small>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" 
                                                   name="quantities[<?= $item['id'] ?>]" 
                                                   value="<?= $item['quantity'] ?>" 
                                                   min="1" 
                                                   max="10" 
                                                   class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-2">
                                            <strong class="text-success">$<?= number_format($item['total_price'], 2) ?></strong>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" name="remove_item" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-flex gap-2 mt-3">
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
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
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
                                <strong class="text-success">$<?= number_format($total_amount, 2) ?></strong>
                            </div>
                            
                            <?php if ($shipping_amount > 0): ?>
                                <div class="alert alert-info small">
                                    <i class="fas fa-shipping-fast me-2"></i>
                                    Add $<?= number_format(100 - $subtotal, 2) ?> more for FREE shipping!
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
    <button type="submit" name="checkout" class="btn btn-success w-100 btn-lg">
        <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
    </button>
</form>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-lock me-1"></i>Secure checkout
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h3>Your Cart is Empty</h3>
                <p class="text-muted mb-4">You haven't added any products to your cart yet.</p>
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>