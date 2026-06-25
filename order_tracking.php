<?php
session_start();
require_once 'config.php';

// Check if order number is provided
$order_number = $_GET['order_number'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_number = trim($_POST['order_number']);
}

try {
    
    
    $order = null;
    $order_items = [];
    
    if ($order_number) {
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, u.username, u.email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.order_number = ?
        ");
        $stmt->execute([$order_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Get order items
            $items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $items_stmt->execute([$order['id']]);
            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Function to get status details
function getStatusDetails($status) {
    $statuses = [
        'pending' => [
            'color' => '#ffc107',
            'icon' => '⏳',
            'description' => 'Your order has been received and is being processed.',
            'steps' => ['pending' => true, 'confirmed' => false, 'processing' => false, 'shipped' => false, 'delivered' => false]
        ],
        'confirmed' => [
            'color' => '#17a2b8',
            'icon' => '✅',
            'description' => 'Your order has been confirmed and is being prepared for shipment.',
            'steps' => ['pending' => true, 'confirmed' => true, 'processing' => false, 'shipped' => false, 'delivered' => false]
        ],
        'processing' => [
            'color' => '#667eea',
            'icon' => '🏭',
            'description' => 'Your order is being processed and packed for shipping.',
            'steps' => ['pending' => true, 'confirmed' => true, 'processing' => true, 'shipped' => false, 'delivered' => false]
        ],
        'shipped' => [
            'color' => '#28a745',
            'icon' => '🚚',
            'description' => 'Your order has been shipped and is on its way to you.',
            'steps' => ['pending' => true, 'confirmed' => true, 'processing' => true, 'shipped' => true, 'delivered' => false]
        ],
        'delivered' => [
            'color' => '#28a745',
            'icon' => '📦',
            'description' => 'Your order has been delivered successfully!',
            'steps' => ['pending' => true, 'confirmed' => true, 'processing' => true, 'shipped' => true, 'delivered' => true]
        ],
        'cancelled' => [
            'color' => '#dc3545',
            'icon' => '❌',
            'description' => 'This order has been cancelled.',
            'steps' => ['pending' => true, 'confirmed' => false, 'processing' => false, 'shipped' => false, 'delivered' => false]
        ]
    ];
    
    return $statuses[$status] ?? $statuses['pending'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking - Online Market</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background: #f8f9fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .tracking-container {
            margin: 30px 0 50px;
        }
        
        .search-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .search-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .search-subtitle {
            color: #666;
            margin-bottom: 25px;
        }
        
        .search-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .order-details {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .order-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }
        
        .tracking-steps {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }
        
        .tracking-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 16px;
        }
        
        .step.active .step-icon {
            background: #667eea;
            color: white;
        }
        
        .step.completed .step-icon {
            background: #28a745;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: #667eea;
            font-weight: bold;
        }
        
        .step.completed .step-label {
            color: #28a745;
        }
        
        .status-description {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 16px;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .order-items, .order-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .summary-total {
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .not-found {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .not-found-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .help-text {
            text-align: center;
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="nav">
                <div class="logo">🏪 Online Market</div>
                <div class="nav-links">
                    <a href="index.html">Home</a>
                    <a href="products.php">Products</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php">Dashboard</a>
                        <a href="cart.php">Cart</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.html">Login</a>
                        <a href="register.html">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="tracking-container">
            <!-- Search Section -->
            <div class="search-section">
                <h1 class="search-title">Track Your Order</h1>
                <p class="search-subtitle">Enter your order number to check the current status</p>
                
                <form method="POST" class="search-form">
                    <div class="form-group">
                        <input type="text" name="order_number" placeholder="Enter order number (e.g., ORD-20251001-XXXXXX)" 
                               value="<?php echo htmlspecialchars($order_number); ?>" required>
                    </div>
                    <button type="submit" class="btn">Track Order</button>
                </form>
                
                <p class="help-text">
                    Your order number can be found in your order confirmation email or in your dashboard.
                </p>
            </div>
            
            <?php if($order_number && !$order): ?>
                <!-- Order Not Found -->
                <div class="not-found">
                    <div class="not-found-icon">🔍</div>
                    <h2>Order Not Found</h2>
                    <p>We couldn't find an order with the number: <strong><?php echo htmlspecialchars($order_number); ?></strong></p>
                    <p>Please check your order number and try again.</p>
                </div>
            <?php elseif($order): ?>
                <?php 
                $status_details = getStatusDetails($order['order_status']);
                $steps = $status_details['steps'];
                ?>
                
                <!-- Order Found -->
                <div class="order-details">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div>
                            <h2 class="order-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                            <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <span class="status-badge" style="background: <?php echo $status_details['color']; ?>">
                            <?php echo $status_details['icon']; ?> <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                    
                    <!-- Tracking Steps -->
                    <div class="tracking-steps">
                        <div class="step <?php echo $steps['pending'] ? 'completed' : ''; ?> <?php echo $order['order_status'] === 'pending' ? 'active' : ''; ?>">
                            <div class="step-icon">📥</div>
                            <div class="step-label">Order Placed</div>
                        </div>
                        <div class="step <?php echo $steps['confirmed'] ? 'completed' : ''; ?> <?php echo $order['order_status'] === 'confirmed' ? 'active' : ''; ?>">
                            <div class="step-icon">✅</div>
                            <div class="step-label">Confirmed</div>
                        </div>
                        <div class="step <?php echo $steps['processing'] ? 'completed' : ''; ?> <?php echo $order['order_status'] === 'processing' ? 'active' : ''; ?>">
                            <div class="step-icon">🏭</div>
                            <div class="step-label">Processing</div>
                        </div>
                        <div class="step <?php echo $steps['shipped'] ? 'completed' : ''; ?> <?php echo $order['order_status'] === 'shipped' ? 'active' : ''; ?>">
                            <div class="step-icon">🚚</div>
                            <div class="step-label">Shipped</div>
                        </div>
                        <div class="step <?php echo $steps['delivered'] ? 'completed' : ''; ?> <?php echo $order['order_status'] === 'delivered' ? 'active' : ''; ?>">
                            <div class="step-icon">📦</div>
                            <div class="step-label">Delivered</div>
                        </div>
                    </div>
                    
                    <!-- Status Description -->
                    <div class="status-description">
                        <?php echo $status_details['icon']; ?> <?php echo $status_details['description']; ?>
                    </div>
                    
                    <!-- Order Details Grid -->
                    <div class="order-info-grid">
                        <!-- Order Items -->
                        <div class="order-items">
                            <h3>Order Items</h3>
                            <?php foreach($order_items as $item): ?>
                                <div class="order-item">
                                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                    <span>$<?php echo number_format($item['total_price'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="order-summary">
                            <h3>Order Summary</h3>
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
                    </div>
                    
                    <!-- Additional Info -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                        <p><strong>Need Help?</strong> If you have any questions about your order, please contact our customer support.</p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <p style="margin-top: 10px;">
                                <a href="dashboard.php" style="color: #667eea; text-decoration: none;">← Back to Your Dashboard</a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>