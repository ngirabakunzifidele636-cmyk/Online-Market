<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    header('Location: admin_orders.php');
    exit();
}

try {
    $pdo = $conn;
    
    // Get order details - using COALESCE to handle missing fields
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COALESCE(u.username, 'Unknown') as username, 
               COALESCE(u.email, 'No email') as email, 
               COALESCE(u.first_name, '') as first_name, 
               COALESCE(u.last_name, '') as last_name, 
               COALESCE(u.phone, '') as phone 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: admin_orders.php');
        exit();
    }
    
    // Get order items
    $items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_order_status'])) {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_POST['order_status'], $order_id]);
            $success = "Order status updated successfully!";
            
            // Reload order data
            $stmt = $pdo->prepare("
                SELECT o.*, 
                       COALESCE(u.username, 'Unknown') as username, 
                       COALESCE(u.email, 'No email') as email, 
                       COALESCE(u.first_name, '') as first_name, 
                       COALESCE(u.last_name, '') as last_name, 
                       COALESCE(u.phone, '') as phone 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        elseif (isset($_POST['update_payment_status'])) {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_POST['payment_status'], $order_id]);
            $success = "Payment status updated successfully!";
            
            // Reload order data
            $stmt = $pdo->prepare("
                SELECT o.*, 
                       COALESCE(u.username, 'Unknown') as username, 
                       COALESCE(u.email, 'No email') as email, 
                       COALESCE(u.first_name, '') as first_name, 
                       COALESCE(u.last_name, '') as last_name, 
                       COALESCE(u.phone, '') as phone 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Function to get status badge 
function getStatusBadge($status) {
    $colors = [
        'pending' => '#ffc107',
        'confirmed' => '#17a2b8',
        'processing' => '#667eea',
        'shipped' => '#28a745',
        'delivered' => '#28a745',
        'cancelled' => '#dc3545',
        'paid' => '#28a745',
        'failed' => '#dc3545'
    ];
    return $colors[$status] ?? '#6c757d';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin Panel</title>
    <style>
        /* Reuse admin styles */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Arial', sans-serif; }
        body { background: #f8f9fa; color: #333; }
        .admin-header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 15px 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .admin-nav { display: flex; justify-content: space-between; align-items: center; }
        .admin-logo { font-size: 20px; font-weight: bold; }
        .admin-links a { color: white; text-decoration: none; margin-left: 20px; padding: 8px 16px; border-radius: 5px; transition: background 0.3s; }
        .admin-links a:hover { background: rgba(255,255,255,0.2); }
        
        .admin-container { margin: 20px 0; }
        .admin-section { background: white; border-radius: 10px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        
        .order-header { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .order-info, .customer-info { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .info-line { margin-bottom: 8px; }
        .status-badge { padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; color: white; display: inline-block; }
        
        .order-items-table { width: 100%; border-collapse: collapse; }
        .order-items-table th, .order-items-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .order-items-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .order-summary, .status-controls { background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-total { border-top: 2px solid #ddd; padding-top: 10px; font-weight: bold; font-size: 18px; }
        
        .btn { background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #5a6fd8; }
        select { padding: 8px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; }
        
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="admin-nav">
                <div class="admin-logo">⚙️ Admin Panel</div>
                <div class="admin-links">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <a href="admin_products.php">Products</a>
                    <a href="admin_orders.php">Orders</a>
                    <a href="admin_users.php">Users</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
<!-- <a href="admin_reports.php">Reports</a> -->
    <div class="container">
        <div class="admin-container">
            <?php if(isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Order Header -->
            <div class="admin-section">
                <h1 class="section-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                
                <div class="order-header">
                    <div class="order-info">
                        <h3>Order Information</h3>
                        <div class="info-line"><strong>Order Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></div>
                        <div class="info-line"><strong>Order Status:</strong> 
                            <span class="status-badge" style="background: <?php echo getStatusBadge($order['order_status']); ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </div>
                        <div class="info-line"><strong>Payment Status:</strong> 
                            <span class="status-badge" style="background: <?php echo getStatusBadge($order['payment_status']); ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                        <div class="info-line"><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method'] ?? 'Not specified'); ?></div>
                    </div>
                    
                    <div class="customer-info">
                        <h3>Customer Information</h3>
                        <div class="info-line"><strong>Customer:</strong> 
                            <?php 
                            $customer_name = trim($order['first_name'] . ' ' . $order['last_name']);
                            echo !empty($customer_name) ? htmlspecialchars($customer_name) : 'Unknown Customer';
                            ?>
                        </div>
                        <div class="info-line"><strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?></div>
                        <div class="info-line"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></div>
                        <div class="info-line"><strong>Phone:</strong> <?php echo !empty($order['phone']) ? htmlspecialchars($order['phone']) : 'Not provided'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="admin-section">
                <h2 class="section-title">Order Items</h2>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($order_items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                </td>
                                <td>$<?php echo number_format($item['product_price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Order Summary & Controls -->
            <div class="summary-grid">
                <div class="order-summary">
                    <h3 class="section-title">Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>$<?php echo number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>
                            <?php if($order['shipping_amount'] == 0): ?>
                                <span style="color: #28a745;">FREE</span>
                            <?php else: ?>
                                $<?php echo number_format($order['shipping_amount'], 2); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
                
                <div class="status-controls">
                    <h3 class="section-title">Update Status</h3>
                    
                    <form method="POST" style="margin-bottom: 15px;">
                        <label><strong>Order Status:</strong></label><br>
                        <select name="order_status" style="width: 100%; margin-bottom: 10px;">
                            <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_order_status" class="btn" style="width: 100%;">Update Order Status</button>
                    </form>
                    
                    <form method="POST">
                        <label><strong>Payment Status:</strong></label><br>
                        <select name="payment_status" style="width: 100%; margin-bottom: 10px;">
                            <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                        <button type="submit" name="update_payment_status" class="btn" style="width: 100%;">Update Payment Status</button>
                    </form>
                </div>
            </div>
            
            <!-- Shipping Information -->
            <div class="admin-section">
                <h2 class="section-title">Shipping Information</h2>
                <div style="white-space: pre-line; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <?php echo htmlspecialchars($order['shipping_address']); ?>
                </div>
                
                <?php if(!empty($order['customer_notes'])): ?>
                    <div style="margin-top: 15px;">
                        <strong>Customer Notes:</strong><br>
                        <?php echo htmlspecialchars($order['customer_notes']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 20px; ">
                <a href="admin_orders.php" class="btn">← Back to Orders</a>
                <a href="admin_reports.php" name="reports" class="btn btn-warning">Reports</a>
                
            </div>
        </div>
    </div>
</body>
</html>