<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle order deletion from orders list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    
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
        } else {
            $_SESSION['error_message'] = "This order cannot be deleted.";
        }
        
        header("Location: orders.php");
        exit();
        
    } catch(PDOException $e) {
        error_log("Error deleting order: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to delete order. Please try again.";
        header("Location: orders.php");
        exit();
    }
}

try {
    $pdo = $conn;
    
    // Get user's orders
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Database error in orders.php: " . $e->getMessage());
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
    return $colors[$status] ?? 'status-default';
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
    return $icons[$status] ?? '📋';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - TechShop</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .orders-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1200px;
        }
        
        .orders-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .orders-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .orders-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .orders-content {
            padding: 40px;
        }
        
        .order-card {
            background: var(--light);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .order-number {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .order-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            align-items: center;
        }
        
        .order-items {
            color: #495057;
        }
        
        .order-total {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
        
        .view-details-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .view-details-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
        
        .order-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: nowrap;
        }
        
        .delete-btn-sm {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .delete-btn-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
            }
            
            .orders-header {
                padding: 30px 20px;
            }
            
            .orders-title {
                font-size: 2rem;
            }
            
            .orders-content {
                padding: 20px;
            }
            
            .order-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .order-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-laptop me-2"></i>TechShop
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
        <div class="orders-container">
            <!-- Orders Header -->
            <div class="orders-header">
                <h1 class="orders-title">My Orders</h1>
                <p class="orders-subtitle">
                    <i class="fas fa-history me-2"></i>
                    View your order history and track your purchases
                </p>
            </div>

            <!-- Orders Content -->
            <div class="orders-content">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <?php if (!empty($orders)): ?>
                    <div class="orders-list">
                        <?php foreach($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-number">
                                        <i class="fas fa-receipt me-2"></i>
                                        Order #<?php echo htmlspecialchars($order['order_number']); ?>
                                    </div>
                                    <div class="order-date">
                                        <i class="far fa-calendar me-2"></i>
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="order-details">
                                    <div class="order-items">
                                        <i class="fas fa-box me-2"></i>
                                        <?php 
                                        // Get item count for this order
                                        try {
                                            $item_stmt = $pdo->prepare("SELECT COUNT(*) as item_count FROM order_items WHERE order_id = ?");
                                            $item_stmt->execute([$order['id']]);
                                            $item_count = $item_stmt->fetch(PDO::FETCH_ASSOC)['item_count'];
                                            echo $item_count . ' item' . ($item_count != 1 ? 's' : '');
                                        } catch(PDOException $e) {
                                            echo 'Items information unavailable';
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="order-total">
                                        $<?php echo number_format($order['total_amount'], 2); ?>
                                    </div>
                                    
                                    <div class="order-status">
                                        <span class="status-badge <?php echo getStatusBadge($order['status']); ?>">
                                            <?php echo getStatusIcon($order['status']); ?>
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="view-details-btn">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </a>
                                        <?php if (in_array($order['status'], ['cancelled', 'delivered'])): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete order #<?php echo htmlspecialchars($order['order_number']); ?>? This action cannot be undone.');">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="delete_order" class="delete-btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <h3>No Orders Yet</h3>
                        <p class="mb-4">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="products.php" class="back-btn">
                                <i class="fas fa-shopping-bag"></i>
                                Start Shopping
                            </a>
                            <a href="create_test_order.php" class="back-btn" style="background: linear-gradient(135deg, #6c757d, #5a6268);">
                                <i class="fas fa-plus"></i>
                                Create Test Order
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Back Button -->
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include 'footer.php'; ?>