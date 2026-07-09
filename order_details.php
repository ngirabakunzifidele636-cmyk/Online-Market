<?php
session_start();
require_once 'config.php';
// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $pdo = $conn;
        
        // Check if order belongs to user and is in cancellable state
        $check_stmt = $pdo->prepare("SELECT order_status, order_number FROM orders WHERE id = ? AND user_id = ?");
        $check_stmt->execute([$order_id, $user_id]);
        $order_check = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order_check && in_array($order_check['order_status'], ['pending', 'confirmed'])) {
            // Update order status to cancelled
            $cancel_stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?");
            $cancel_stmt->execute([$order_id, $user_id]);
            
            $_SESSION['success_message'] = "Order #{$order_check['order_number']} has been cancelled successfully.";
        } else {
            $_SESSION['error_message'] = "This order cannot be cancelled.";
        }
        
        header("Location: order_details.php?order_id=" . $order_id);
        exit();
        
    } catch(PDOException $e) {
        error_log("Error cancelling order: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to cancel order. Please try again.";
        header("Location: order_details.php?order_id=" . $order_id);
        exit();
    }
}

// Handle order deletion from details page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $pdo = $conn;
        
        // Check if order belongs to user and can be deleted
        $check_stmt = $pdo->prepare("SELECT order_status, order_number FROM orders WHERE id = ? AND user_id = ?");
        $check_stmt->execute([$order_id, $user_id]);
        $order_check = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order_check && in_array($order_check['order_status'], ['cancelled', 'delivered'])) {
            // Delete order items first
            $delete_items = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $delete_items->execute([$order_id]);
            
            // Delete the order
            $delete_order = $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
            $delete_order->execute([$order_id, $user_id]);
            
            $_SESSION['success_message'] = "Order #{$order_check['order_number']} has been deleted.";
            header("Location: orders.php");
            exit();
        } else {
            $_SESSION['error_message'] = "This order cannot be deleted. Only cancelled or delivered orders can be deleted.";
            header("Location: order_details.php?order_id=" . $order_id);
            exit();
        }
        
    } catch(PDOException $e) {
        error_log("Error deleting order: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to delete order. Please try again.";
        header("Location: order_details.php?order_id=" . $order_id);
        exit();
    }
}
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Accept both 'order_id' and 'id' parameters
$order_id = $_GET['order_id'] ?? $_GET['id'] ?? null;

if (!$order_id) {
    $_SESSION['error_message'] = "No order specified.";
    header('Location: dashboard.php');
    exit();
}

try {
    $pdo = $conn;
    
    // Check if order exists
    $check_stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $check_stmt->execute([$order_id]);
    $order_exists = $check_stmt->fetch();
    
    if (!$order_exists) {
        $_SESSION['error_message'] = "Order not found.";
        header('Location: dashboard.php');
        exit();
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email, u.first_name, u.last_name, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error_message'] = "Order not found or you don't have permission to view it.";
        header('Location: dashboard.php');
        exit();
    }
    
    // Get order items WITH product images
    $items_stmt = $pdo->prepare("
        SELECT oi.*, product_image as product_image 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Database error in order_details: " . $e->getMessage());
    die("Database error: " . $e->getMessage());
}

// Function to get status badge color
function getStatusBadge($status) {
    $colors = [
        'pending' => 'status-pending',
        'confirmed' => 'status-confirmed',
        'processing' => 'status-processing',
        'shipped' => 'status-shipped',
        'delivered' => 'status-delivered',
        'cancelled' => 'status-cancelled',
        'paid' => 'status-paid',
        'failed' => 'status-failed'
    ];
    return $colors[strtolower($status)] ?? 'status-default';
}

// Function to get status icon
function getStatusIcon($status) {
    $icons = [
        'pending' => '⏳',
        'confirmed' => '✅',
        'processing' => '🔄',
        'shipped' => '🚚',
        'delivered' => '📦',
        'cancelled' => '❌',
        'paid' => '💳',
        'failed' => '⚠️'
    ];
    return $icons[strtolower($status)] ?? '📋';
}

// Function to get product image - UPDATED to use stored image or fallback
function getProductImage($order_item) {
    // First try to use the stored product image from order_items
    if (!empty($order_item['product_image'])) {
        return $order_item['product_image'];
    }
    
    // Fallback to product name mapping (your existing logic)
    $product_name = $order_item['product_name'];
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
    
    // Final fallback
    $fallback_images = [
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=300&fit=crop',
        'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop'
    ];
    
    return $fallback_images[array_rand($fallback_images)];
}

// Function to get product category based on name
function getProductCategory($product_name) {
    $product_name_lower = strtolower($product_name);
    
    if (strpos($product_name_lower, 'phone') !== false || 
        strpos($product_name_lower, 'laptop') !== false ||
        strpos($product_name_lower, 'usb') !== false ||
        strpos($product_name_lower, 'headphone') !== false ||
        strpos($product_name_lower, 'bluetooth') !== false ||
        strpos($product_name_lower, 'cable') !== false ||
        strpos($product_name_lower, 'screen') !== false) {
        return 'Electronics';
    }
    
    if (strpos($product_name_lower, 'case') !== false || 
        strpos($product_name_lower, 'sleeve') !== false ||
        strpos($product_name_lower, 'protector') !== false) {
        return 'Accessories';
    }
    
    return 'General';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo htmlspecialchars($order['order_number']); ?> - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .order-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1200px;
        }
        
        .order-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .order-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .order-meta {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .status-badges {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .status-pending { background: linear-gradient(135deg, #ffc107, #e0a800); }
        .status-confirmed { background: linear-gradient(135deg, #17a2b8, #138496); }
        .status-processing { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .status-shipped { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .status-delivered { background: linear-gradient(135deg, #20c997, #199d7a); }
        .status-cancelled { background: linear-gradient(135deg, #dc3545, #c82333); }
        .status-paid { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .status-failed { background: linear-gradient(135deg, #dc3545, #c82333); }
        .status-default { background: linear-gradient(135deg, #6c757d, #5a6268); }
        
        .order-content {
            padding: 40px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-items-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            border-radius: 15px;
            background: var(--light);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .order-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .item-category {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .item-price {
            color: var(--primary);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .item-total {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.2rem;
        }
        
        .order-summary-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            margin-bottom: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .summary-total {
            border-top: 2px solid rgba(255,255,255,0.3);
            padding-top: 15px;
            margin-top: 10px;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .shipping-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: var(--light);
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .info-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        
        .info-content {
            color: #495057;
            line-height: 1.6;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            border: none;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            color: white;
        }
        
        .progress-tracker {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 40px 0;
            padding: 0 20px;
        }
        
        .progress-tracker::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50px;
            right: 50px;
            height: 4px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-weight: bold;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .step-active .step-icon {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: scale(1.1);
        }
        
        .step-completed .step-icon {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }
        
        .step-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            font-weight: 500;
        }
        
        .step-active .step-label {
            color: var(--primary);
            font-weight: 600;
        }
        
        .step-completed .step-label {
            color: var(--success);
        }
        
        @media (max-width: 768px) {
            .order-header {
                padding: 30px 20px;
            }
            
            .order-title {
                font-size: 2rem;
            }
            
            .order-content {
                padding: 20px;
            }
            
            .order-item {
                flex-direction: column;
                text-align: center;
            }
            
            .progress-tracker {
                flex-direction: column;
                gap: 20px;
            }
            
            .progress-tracker::before {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-laptop-code me-2"></i>TechShop
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                <a class="nav-link" href="products.php"><i class="fas fa-shopping-bag me-1"></i>Products</a>
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                <a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart me-1"></i>Cart</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="order-container">
            <!-- Order Header -->
            <div class="order-header">
                <h1 class="order-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                <div class="order-meta">
                    <i class="far fa-calendar me-2"></i>
                    Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                </div>
                <div class="status-badges">
                    <!-- <span class="status-badge <?php echo getStatusBadge($order['order_status']); ?>">
                        <?php echo getStatusIcon($order['sorder_status']); ?>
                        Order: <?php echo ucfirst($order['order_status']); ?>
                    </span> -->
                    <span class="status-badge <?php echo getStatusBadge($order['order_status']); ?>">
    <?php echo getStatusIcon($order['order_status']); ?>
    Order: <?php echo ucfirst($order['order_status']); ?>
</span>
                    <span class="status-badge <?php echo getStatusBadge($order['payment_status']); ?>">
                        <i class="fas fa-credit-card"></i>
                        Payment: <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                    
                </div>
            </div>

            <!-- Order Progress -->
<div class="order-content">
    <div class="progress-tracker">
        <?php
        $steps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
        $currentStep = array_search($order['order_status'], $steps);
        $currentStep = $currentStep !== false ? $currentStep : 0;
        ?>
        <?php foreach($steps as $index => $step): ?>
            <div class="progress-step <?php echo $index < $currentStep ? 'step-completed' : ($index == $currentStep ? 'step-active' : ''); ?>">
                <div class="step-icon">
                    <?php if($index < $currentStep): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <?php echo $index + 1; ?>
                    <?php endif; ?>
                </div>
                <div class="step-label"><?php echo ucfirst($step); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

                <!-- Order Items -->
                <h2 class="section-title">
                    <i class="fas fa-shopping-basket"></i>
                    Order Items (<?php echo count($order_items); ?>)
                </h2>
                <div class="order-items-grid">
                    <?php if (!empty($order_items)): ?>
                        <?php foreach($order_items as $item): ?>
                            <div class="order-item">
                                <!-- UPDATED: Use the actual product image from the database -->
                                <img src="<?php echo getProductImage($item); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                     class="item-image">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="item-category">
                                        <i class="fas fa-tag"></i>
                                        <?php echo getProductCategory($item['product_name']); ?>
                                    </div>
                                    <div class="item-price">
                                        $<?php echo number_format($item['unit_price'], 2); ?> x <?php echo $item['quantity']; ?> units
                                    </div>
                                </div>
                                <div class="item-total">
                                    $<?php echo number_format($item['total_price'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-basket fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No items found for this order.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Summary -->
                <div class="order-summary-card">
                    <h2 class="section-title text-white">
                        <i class="fas fa-receipt"></i>
                        Order Summary
                    </h2>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($order['subtotal'] ?? $order['total_amount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>$<?php echo number_format($order['tax_amount'] ?? 0, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>
                            <?php if(($order['shipping_amount'] ?? 0) == 0): ?>
                                FREE
                            <?php else: ?>
                                $<?php echo number_format($order['shipping_amount'], 2); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total Amount:</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>

                <!-- Shipping Information -->
                <h2 class="section-title">
                    <i class="fas fa-shipping-fast"></i>
                    Order Information
                </h2>
                <div class="shipping-info-grid">
                    <div class="info-card">
                        <div class="info-title">
                            <i class="fas fa-user"></i>
                            Customer Details
                        </div>
                        <div class="info-content">
                            <strong><?php echo htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? $order['username'])); ?></strong><br>
                            <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($order['email']); ?><br>
                            <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($order['phone'] ?? 'Not provided'); ?>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Shipping Address
                        </div>
                        <div class="info-content">
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-title">
                            <i class="fas fa-credit-card"></i>
                            Payment Information
                        </div>
                        <div class="info-content">
                            <strong>Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?><br>
                            <strong>Status:</strong> 
                            <span class="status-badge <?php echo getStatusBadge($order['order_status']); ?>">
                        <?php echo getStatusIcon($order['order_status']); ?>
                               Order: <?php echo ucfirst($order['order_status']); ?>
                             </span>
                        </div>
                    </div>
                    
                    <?php if(!empty($order['customer_notes'])): ?>
                    <div class="info-card">
                        <div class="info-title">
                            <i class="fas fa-sticky-note"></i>
                            Order Notes
                        </div>
                        <div class="info-content">
                            <?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="text-center">
                    <a href="orders.php" class="back-btn me-3">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <a href="products.php" class="back-btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-shopping-bag"></i>
                        Continue Shopping
                    </a>
                  
<div class="text-center mt-4" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
    <?php if (in_array($order['order_status'], ['pending', 'confirmed'])): ?>
        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to cancel this order?');">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            <button type="submit" name="cancel_order" class="back-btn" style="background: linear-gradient(135deg, #ffc107, #e0a800); box-shadow: 0 5px 15px rgba(255, 193, 7, 0.4);">
                <i class="fas fa-times"></i>
                Cancel Order
            </button>
        </form>
    <?php endif; ?>
    
    <?php if (in_array($order['order_status'], ['cancelled', 'delivered'])): ?>
        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this order? This action cannot be undone.');">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            <button type="submit" name="delete_order" class="back-btn" style="background: linear-gradient(135deg, #dc3545, #c82333); box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);">
                <i class="fas fa-trash"></i>
                Delete Order
            </button>
        </form>
    <?php endif; ?>
</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>