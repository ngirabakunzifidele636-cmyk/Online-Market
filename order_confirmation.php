<?php
// order_confirmation.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['order_success'])) {
    header('Location: cart.php');
    exit();
}

$order_number = $_SESSION['order_number'];
$order_total = $_SESSION['order_total'];
$email_sent = $_SESSION['email_sent'] ?? false;

// Clear the session variables
unset($_SESSION['order_success']);
unset($_SESSION['order_number']);
unset($_SESSION['order_total']);
unset($_SESSION['order_id']);
unset($_SESSION['email_sent']);
try {
    $user_id = $_SESSION['user_id'];
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

    $notif_stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $notif_stmt->execute([
        $user_id,
        '✅ Order Confirmed',
        'Your order #' . $order_number . ' has been confirmed. Total amount: $' . number_format($order_total, 2),
        'success',
        'fa-check-circle',
        'order',
        'orders.php'
    ]);
} catch (PDOException $e) {
    error_log("Failed to create confirmation notification: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .confirmation-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .content {
            padding: 40px;
            text-align: center;
        }
        .order-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            margin: 20px 0;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .email-status {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="confirmation-container">
            <div class="header">
                <i class="fas fa-check-circle fa-4x mb-3"></i>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your purchase</p>
            </div>
            
            <div class="content">
                <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                
                <div class="order-number">
                    Order #: <?= htmlspecialchars($order_number) ?>
                </div>
                
                <p class="mb-4">
                    Your order has been successfully placed and is being processed.
                    Total amount: <strong>$<?= number_format($order_total, 2) ?></strong>
                </p>
                
                <div class="email-status">
                    <?php if ($email_sent): ?>
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Confirmation email sent to your inbox
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle text-warning me-2"></i>
                        Email confirmation could not be sent
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <a href="orders.php" class="btn-custom">
                        <i class="fas fa-box me-2"></i>View My Orders
                    </a>
                    <a href="products.php" class="btn-custom" style="background: linear-gradient(135deg, #6c757d, #5a6268);">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
                
                <p class="mt-4 text-muted small">
                    You will receive a confirmation email shortly with your order details.
                    You can track your order status from your dashboard.
                </p>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>