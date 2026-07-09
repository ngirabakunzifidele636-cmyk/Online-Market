<?php
session_start();
require_once 'db_connection.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

try {
    $pdo = getDatabaseConnection();
    $conn = getDatabaseConnection();
    
    // Handle order actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_status'])) {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_POST['order_status'], $_POST['order_id']]);
            $success = "Order status updated successfully!";
            
            // Create notification for user
            try {
                $order_stmt = $pdo->prepare("SELECT o.*, u.id as user_id, u.email, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
                $order_stmt->execute([$_POST['order_id']]);
                $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    $table_check = $pdo->query("SHOW TABLES LIKE 'notifications'")->fetch();
                    if (!$table_check) {
                        $pdo->exec("
                            CREATE TABLE notifications (
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
                                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                                INDEX idx_user_read (user_id, is_read)
                            )
                        ");
                    }
                    
                    $notif_stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $action_url = 'order_details.php?order_id=' . $_POST['order_id'];
                    $status_messages = [
                        'pending' => 'Your order is pending review.',
                        'confirmed' => 'Your order has been confirmed and is being processed.',
                        'processing' => 'Your order is currently being processed.',
                        'shipped' => 'Your order has been shipped! Track your delivery.',
                        'delivered' => 'Your order has been delivered. Thank you for shopping with us!',
                        'cancelled' => 'Your order has been cancelled.'
                    ];
                    
                    $notif_stmt->execute([
                        $order['user_id'],
                        '📦 Order Status Updated',
                        'Your order #' . $order['order_number'] . ' status changed to: ' . ucfirst($_POST['order_status']) . '. ' . ($status_messages[$_POST['order_status']] ?? ''),
                        'info',
                        'fa-truck',
                        'order',
                        $action_url
                    ]);
                }
            } catch (PDOException $e) {
                error_log("Failed to create notification: " . $e->getMessage());
            }
        }
        elseif (isset($_POST['update_payment_status'])) {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_POST['payment_status'], $_POST['order_id']]);
            $success = "Payment status updated successfully!";
        }
        
        // Handle order deletion
        if (isset($_POST['delete_order'])) {
            $order_id = $_POST['order_id'];
            
            try {
                // Get order details before deletion for notification
                $order_stmt = $pdo->prepare("SELECT o.*, u.id as user_id, u.email, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
                $order_stmt->execute([$order_id]);
                $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    // Delete order items first (due to foreign key constraint)
                    $delete_items = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
                    $delete_items->execute([$order_id]);
                    
                    // Delete the order
                    $delete_order = $pdo->prepare("DELETE FROM orders WHERE id = ?");
                    $delete_order->execute([$order_id]);
                    
                    $success = "Order #{$order['order_number']} deleted successfully!";
                    
                    // Create notification for the user
                    try {
                        $table_check = $pdo->query("SHOW TABLES LIKE 'notifications'")->fetch();
                        if (!$table_check) {
                            $pdo->exec("
                                CREATE TABLE notifications (
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
                                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                                    INDEX idx_user_read (user_id, is_read)
                                )
                            ");
                        }
                        
                        $notif_stmt = $pdo->prepare("
                            INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        
                        $action_url = 'order_details.php?order_id=' . $order_id;
                        
                        $notif_stmt->execute([
                            $order['user_id'],
                            '🗑️ Order Deleted',
                            'Your order #' . $order['order_number'] . ' has been deleted by an administrator.',
                            'danger',
                            'fa-trash',
                            'order',
                            $action_url
                        ]);
                    } catch (PDOException $e) {
                        error_log("Failed to create notification: " . $e->getMessage());
                    }
                }
            } catch (PDOException $e) {
                error_log("Failed to delete order: " . $e->getMessage());
                $error = "Failed to delete order. Please try again.";
            }
        }
    }
    
    // Get filter parameters
    $status_filter = $_GET['status'] ?? '';
    $payment_filter = $_GET['payment_status'] ?? '';
    
    // Build query with filters
    $query = "
        SELECT o.*, u.username, u.email, COUNT(oi.id) as item_count 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
    ";
    
    $where_conditions = [];
    $params = [];
    
    if ($status_filter) {
        $where_conditions[] = "o.order_status = ?";
        $params[] = $status_filter;
    }
    
    if ($payment_filter) {
        $where_conditions[] = "o.payment_status = ?";
        $params[] = $payment_filter;
    }
    
    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $query .= " GROUP BY o.id ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status counts for filters
    $status_counts = $pdo->query("
        SELECT order_status, COUNT(*) as count 
        FROM orders 
        GROUP BY order_status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $payment_counts = $pdo->query("
        SELECT payment_status, COUNT(*) as count 
        FROM orders 
        GROUP BY payment_status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Function to get status badge color
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
    <title>Manage Orders - Admin Panel</title>
    <style>
        /* Reuse admin styles */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Arial', sans-serif; }
        body { background: #f8f9fa; color: #333; }
        .admin-header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 15px 0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .admin-nav { display: flex; justify-content: space-between; align-items: center; }
        .admin-logo { font-size: 20px; font-weight: bold; }
        .admin-links a { color: white; text-decoration: none; margin-left: 20px; padding: 8px 16px; border-radius: 5px; transition: background 0.3s; }
        .admin-links a:hover { background: rgba(255,255,255,0.2); }
        .admin-links a.active { background: #667eea; }
        
        .admin-container { margin: 20px 0; }
        .admin-section { background: white; border-radius: 10px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-group { display: flex; align-items: center; gap: 8px; }
        select { padding: 8px 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; }
        .btn { background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; }
        .btn:hover { background: #5a6fd8; }
        .btn-outline { background: white; border: 2px solid #667eea; color: #667eea; }
        .btn-outline:hover { background: #667eea; color: white; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th, .orders-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        .orders-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        
        .action-form { display: inline; }
        .action-select { padding: 4px 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; }
        .update-btn { padding: 4px 8px; font-size: 11px; background: #27ae60; color: white; border: none; border-radius: 3px; cursor: pointer; }
        
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .filter-badge { background: #e9ecef; padding: 4px 8px; border-radius: 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .delete-btn { background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px; }
        .delete-btn:hover { background: #c82333; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: nowrap; align-items: center; }
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
                    <a href="admin_orders.php" class="active">Orders</a>
                    <a href="admin_users.php">Users</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="admin-container">
            <?php if(isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="admin-section">
                <h2 class="section-title">🔍 Order Filters</h2>
                
                <div class="filters">
                    <div class="filter-group">
                        <label>Order Status:</label>
                        <select onchange="window.location.href='admin_orders.php?status='+this.value+'&payment_status=<?php echo $payment_filter; ?>'">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Payment Status:</label>
                        <select onchange="window.location.href='admin_orders.php?status=<?php echo $status_filter; ?>&payment_status='+this.value">
                            <option value="">All Payments</option>
                            <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $payment_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <a href="admin_orders.php" class="btn btn-outline">Clear Filters</a>
                </div>
                
                <!-- Active Filters -->
                <?php if($status_filter || $payment_filter): ?>
                    <div style="margin-top: 15px;">
                        <strong>Active Filters:</strong>
                        <?php if($status_filter): ?>
                            <span class="filter-badge">
                                Order Status: <?php echo ucfirst($status_filter); ?>
                                <a href="admin_orders.php?payment_status=<?php echo $payment_filter; ?>" style="color: #666; text-decoration: none;">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if($payment_filter): ?>
                            <span class="filter-badge">
                                Payment: <?php echo ucfirst($payment_filter); ?>
                                <a href="admin_orders.php?status=<?php echo $status_filter; ?>" style="color: #666; text-decoration: none;">×</a>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Orders List -->
            <div class="admin-section">
                <h2 class="section-title">
                    📋 Orders (<?php echo count($orders); ?>)
                    <?php if($status_filter): ?>
                        - <?php echo ucfirst($status_filter); ?>
                    <?php endif; ?>
                </h2>
                
                <?php if(empty($orders)): ?>
                    <p>No orders found.</p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Order Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($order['username']); ?><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo $order['item_count']; ?> item(s)</td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="order_status" class="action-select" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="payment_status" class="action-select" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                            <input type="hidden" name="update_payment_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="admin_order_details.php?order_id=<?php echo $order['id']; ?>" class="btn">
                                                View Details
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete order #<?php echo htmlspecialchars($order['order_number']); ?>? This action cannot be undone.');">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="delete_order" class="delete-btn">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>