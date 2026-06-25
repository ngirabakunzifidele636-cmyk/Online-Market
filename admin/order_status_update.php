<?php
// order_status_update.php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Get all orders for dropdown
$orders_stmt = $conn->prepare("SELECT id, order_number, user_id, status FROM orders ORDER BY created_at DESC");
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $custom_message = $_POST['custom_message'] ?? '';
    
    try {
        // Get current order details
        $order_stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception("Order not found");
        }
        
        $old_status = $order['status'];
        
        // Update order status
        $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update_stmt->execute([$new_status, $order_id]);
        
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
        
        // Define status icons and messages
        $status_info = [
            'pending' => ['icon' => 'fa-clock', 'color' => 'warning', 'message' => 'Your order is pending'],
            'confirmed' => ['icon' => 'fa-check-circle', 'color' => 'success', 'message' => 'Your order has been confirmed'],
            'processing' => ['icon' => 'fa-cog', 'color' => 'info', 'message' => 'Your order is being processed'],
            'shipped' => ['icon' => 'fa-truck', 'color' => 'primary', 'message' => 'Your order has been shipped'],
            'delivered' => ['icon' => 'fa-box-open', 'color' => 'success', 'message' => 'Your order has been delivered'],
            'cancelled' => ['icon' => 'fa-times-circle', 'color' => 'danger', 'message' => 'Your order has been cancelled']
        ];
        
        $icon = $status_info[$new_status]['icon'] ?? 'fa-bell';
        $type = $status_info[$new_status]['color'] ?? 'info';
        
        // Create notification message
        if (!empty($custom_message)) {
            $message = $custom_message;
        } else {
            $message = $status_info[$new_status]['message'] . ' #' . $order['order_number'];
        }
        
        // Insert notification for user
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $notif_stmt->execute([
            $order['user_id'],
            'Order Status Updated: ' . ucfirst($new_status),
            $message,
            $type,
            $icon,
            'order',
            'order_details.php?id=' . $order_id
        ]);
        
        // Also send email notification (optional)
        // You can integrate your email system here
        
        $success = "Order #" . $order['order_number'] . " status updated to " . ucfirst($new_status) . " and notification sent!";
        
    } catch (Exception $e) {
        $error = "Failed to update order status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order Status - TechShop Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 800px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #17a2b8; color: #fff; }
        .status-processing { background: #007bff; color: #fff; }
        .status-shipped { background: #28a745; color: #fff; }
        .status-delivered { background: #20c997; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="admin-container">
            <div class="header">
                <h1><i class="fas fa-truck me-2"></i>Update Order Status</h1>
                <p class="mb-0">Send notifications to customers when order status changes</p>
            </div>
            
            <div class="p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Order</label>
                        <select name="order_id" class="form-select" required>
                            <option value="">Choose order...</option>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $status_class = '';
                                switch($order['status']) {
                                    case 'pending': $status_class = 'status-pending'; break;
                                    case 'confirmed': $status_class = 'status-confirmed'; break;
                                    case 'processing': $status_class = 'status-processing'; break;
                                    case 'shipped': $status_class = 'status-shipped'; break;
                                    case 'delivered': $status_class = 'status-delivered'; break;
                                    case 'cancelled': $status_class = 'status-cancelled'; break;
                                }
                                ?>
                                <option value="<?= $order['id'] ?>">
                                    #<?= $order['order_number'] ?> - Current: 
                                    <span class="status-badge <?= $status_class ?>"><?= ucfirst($order['status']) ?></span>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">New Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select new status...</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="confirmed">✅ Confirmed</option>
                            <option value="processing">🔄 Processing</option>
                            <option value="shipped">🚚 Shipped</option>
                            <option value="delivered">📦 Delivered</option>
                            <option value="cancelled">❌ Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Custom Message (Optional)</label>
                        <textarea name="custom_message" class="form-control" rows="3" placeholder="Enter a custom message for the customer..."></textarea>
                        <small class="text-muted">If left blank, a default message will be used.</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        When you update the status, the customer will receive a notification in their account.
                    </div>
                    
                    <button type="submit" name="update_status" class="btn btn-primary w-100 btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>Update Status & Send Notification
                    </button>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="admin_orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview selected order
        document.querySelector('select[name="order_id"]').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (selected.value) {
                console.log('Selected order:', selected.text);
            }
        });
    </script>
</body>
</html>
<?php include 'footer.php'; ?>