<?php
// payment_instructions.php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: cart.php');
    exit();
}

$provider = $order['mobile_money_provider'] ?? 'Mobile Money';
$mobile_number = $order['mobile_money_number'] ?? '';

// Check if payment is already confirmed
$payment_confirmed = false;
// You would check payment status from database or payment gateway

// ===== ADD PAYMENT REMINDER NOTIFICATION =====
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

    // Check if we've already sent a payment reminder for this order today
    $check_stmt = $conn->prepare("
        SELECT id FROM notifications 
        WHERE user_id = ? AND category = 'payment' AND message LIKE ? 
        AND DATE(created_at) = CURDATE()
    ");
    $check_stmt->execute([$user_id, '%' . $order['order_number'] . '%']);
    
    // Only send reminder if no reminder sent today
    if ($check_stmt->rowCount() == 0) {
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        // Payment reminder notification
        $notif_stmt->execute([
            $user_id,
            '⏰ Payment Reminder',
            'Please complete your payment of $' . number_format($order['total_amount'], 2) . ' for order #' . $order['order_number'] . ' using ' . $provider . '. Your order will be processed once payment is received.',
            'warning',
            'fa-clock',
            'payment',
            'payment_instructions.php?order_id=' . $order_id
        ]);
        
        // Tips for payment
        $notif_stmt->execute([
            $user_id,
            '💡 Payment Tips',
            'Make sure you have sufficient balance in your ' . $provider . ' account. The payment should reflect within 5 minutes.',
            'info',
            'fa-lightbulb',
            'payment',
            'payment_instructions.php?order_id=' . $order_id
        ]);
    }
    
    // Check if payment is taking too long (more than 2 hours)
    $order_time = strtotime($order['created_at']);
    $current_time = time();
    $hours_passed = ($current_time - $order_time) / 3600;
    
    if ($hours_passed > 2 && $hours_passed < 24) {
        // Check if we've sent a follow-up
        $followup_check = $conn->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND title = '⏳ Payment Follow-up' 
            AND DATE(created_at) = CURDATE()
        ");
        $followup_check->execute([$user_id]);
        
        if ($followup_check->rowCount() == 0) {
            $notif_stmt->execute([
                $user_id,
                '⏳ Payment Follow-up',
                'Your order #' . $order['order_number'] . ' is still awaiting payment. Please complete your payment to avoid order cancellation.',
                'danger',
                'fa-exclamation-triangle',
                'payment',
                'payment_instructions.php?order_id=' . $order_id
            ]);
        }
    }
    
} catch (PDOException $e) {
    error_log("Failed to create payment reminder: " . $e->getMessage());
}
// ===== END NOTIFICATION =====

// Handle payment confirmation (simulated - in real app, this would be from payment gateway)
if (isset($_GET['confirm_payment']) && $_GET['confirm_payment'] == 'true') {
    try {
        // Update order status
        $update_stmt = $conn->prepare("UPDATE orders SET status = 'confirmed', payment_status = 'paid' WHERE id = ?");
        $update_stmt->execute([$order_id]);
        
        // Add payment confirmation notification
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $notif_stmt->execute([
            $user_id,
            '✅ Payment Received',
            'Your payment of $' . number_format($order['total_amount'], 2) . ' for order #' . $order['order_number'] . ' has been received. Your order is now being processed.',
            'success',
            'fa-check-circle',
            'payment',
            'orders.php'
        ]);
        
        $payment_confirmed = true;
        
    } catch (PDOException $e) {
        error_log("Failed to confirm payment: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Instructions - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .instructions-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .provider-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-mtn {
            background-color: #ffc107;
            color: #000;
        }
        .badge-airtel {
            background-color: #dc3545;
            color: #fff;
        }
        .countdown-timer {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
        }
        .payment-status {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .status-pending {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .status-confirmed {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="instructions-container">
            <div class="header">
                <h1><i class="fas fa-mobile-alt me-2"></i>Payment Instructions</h1>
                <p class="mb-0">Complete your mobile money payment</p>
            </div>
            
            <div class="p-4">
                <?php if ($payment_confirmed): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Payment confirmed! Your order is now being processed.
                    </div>
                <?php endif; ?>
                
                <div class="text-center mb-4">
                    <div class="display-1 text-success mb-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="mb-3">Order Placed Successfully!</h2>
                    <p class="text-muted">Your order number is: <strong><?= $order['order_number'] ?></strong></p>
                </div>
                
                <!-- Payment Status -->
                <div class="payment-status <?= $payment_confirmed ? 'status-confirmed' : 'status-pending' ?>">
                    <div class="d-flex align-items-center">
                        <i class="fas <?= $payment_confirmed ? 'fa-check-circle text-success' : 'fa-clock text-warning' ?> fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Payment Status: <strong><?= $payment_confirmed ? 'Confirmed' : 'Pending' ?></strong></h5>
                            <p class="mb-0 small">
                                <?= $payment_confirmed ? 
                                    'Your payment has been received. You will receive a confirmation email shortly.' : 
                                    'Please complete the payment using the instructions below.' ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <?php if (!$payment_confirmed): ?>
                    <!-- Payment Timer -->
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-hourglass-half me-2"></i>
                        Complete payment within: 
                        <span class="countdown-timer" id="timer">30:00</span>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h5 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        Payment Instructions for 
                        <?php if ($provider == 'MoMo'): ?>
                            <span class="provider-badge badge-mtn">MTN MoMo</span>
                        <?php else: ?>
                            <span class="provider-badge badge-airtel">Airtel Money</span>
                        <?php endif; ?>
                    </h5>
                    <p class="mb-2">Please complete your payment using <?= htmlspecialchars($provider) ?>:</p>
                    <hr>
                    <p class="mb-1"><strong>Amount to pay:</strong> $<?= number_format($order['total_amount'], 2) ?></p>
                    <p class="mb-1"><strong>Mobile number:</strong> +250 <?= $mobile_number ?></p>
                    <?php if ($provider == 'MoMo'): ?>
                        <p class="mb-0 mt-2"><small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            MTN MoMo numbers start with 78 or 79 (e.g., 78XXXXXXX)
                        </small></p>
                    <?php else: ?>
                        <p class="mb-0 mt-2"><small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Airtel Money numbers start with 72 or 73 (e.g., 72XXXXXXX)
                        </small></p>
                    <?php endif; ?>
                </div>
                
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h6><i class="fas fa-list-ol me-2"></i>Steps to complete payment:</h6>
                        <ol class="mb-0">
                            <?php if ($provider == 'MoMo'): ?>
                                <li>Dial <strong>*182#</strong> on your MTN line</li>
                                <li>Select option 3 for "Pay Merchant" or "Make Payment"</li>
                                <li>Enter Merchant Code: <strong>123456</strong></li>
                                <li>Enter Reference/Order Number: <strong><?= $order['order_number'] ?></strong></li>
                            <?php else: ?>
                                <li>Dial <strong>*111#</strong> on your Airtel line</li>
                                <li>Select option 2 for "Make Payment"</li>
                                <li>Enter Merchant Code: <strong>123456</strong></li>
                                <li>Enter Order Number: <strong><?= $order['order_number'] ?></strong></li>
                            <?php endif; ?>
                            <li>Enter Amount: <strong>$<?= number_format($order['total_amount'], 2) ?></strong></li>
                            <li>Enter your PIN to confirm the transaction</li>
                            <li>Keep the transaction reference for your records</li>
                        </ol>
                    </div>
                </div>
                
                <!-- Payment Confirmation Button (for demo purposes) -->
                <?php if (!$payment_confirmed): ?>
                    <div class="alert alert-success text-center">
                        <p class="mb-2"><strong>After making payment:</strong></p>
                        <a href="?order_id=<?= $order_id ?>&confirm_payment=true" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-check-circle me-2"></i>I Have Made Payment
                        </a>
                        <small class="text-muted d-block mt-2">
                            Click this button after completing the payment to confirm
                        </small>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-2"></i>
                    Your order will be processed once payment is confirmed. This may take a few minutes.
                    <br>
                    <small>Please do not close this page until you have completed the payment.</small>
                </div>
                
                <div class="text-center mt-4">
                    <a href="orders.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-box me-2"></i>View My Orders
                    </a>
                    <a href="products.php" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!$payment_confirmed): ?>
    <script>
        // Countdown timer (30 minutes)
        let timeLeft = 30 * 60; // 30 minutes in seconds
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timerElement = document.getElementById('timer');
            
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerElement.textContent = "EXPIRED";
                timerElement.style.color = '#dc3545';
                
                // Show expired message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger mt-3';
                alert.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Payment time has expired. Please place your order again.';
                document.querySelector('.alert-warning').after(alert);
            } else {
                timeLeft--;
            }
        }
        
        const timerInterval = setInterval(updateTimer, 1000);
        
        // Warn user before leaving page
        window.addEventListener('beforeunload', function(e) {
            if (timeLeft > 0 && !<?= $payment_confirmed ? 'true' : 'false' ?>) {
                e.preventDefault();
                e.returnValue = 'You have not completed your payment. Are you sure you want to leave?';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
<?php include 'footer.php'; ?>