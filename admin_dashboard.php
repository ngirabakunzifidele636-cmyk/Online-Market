<?php
session_start();
require_once 'config.php';
require_once 'db_connection.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

try {
    $pdo = getDatabaseConnection();
    $conn = getDatabaseConnection();
    
    // Get dashboard statistics
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?? 0;
    
    // Get recent orders
    $recent_orders = $pdo->query("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get low stock products
    $low_stock = $pdo->query("
        SELECT * FROM products 
        WHERE stock_quantity <= low_stock_threshold 
        AND is_active = TRUE 
        ORDER BY stock_quantity ASC 
        LIMIT 10
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
    <title>Admin Dashboard - Online Market</title>
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
        
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-logo {
            font-size: 20px;
            font-weight: bold;
        }
        
        .admin-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .admin-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .admin-links a.active {
            background: #667eea;
        }
        
        .dashboard-container {
            margin: 20px 0;
        }
        
        .welcome-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .welcome-title {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .welcome-subtitle {
            color: #666;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 30px;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        .dashboard-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .orders-table, .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td,
        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .orders-table th,
        .products-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            display: inline-block;
        }
        
        .action-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn:hover {
            background: #5a6fd8;
        }
        
        .btn-small {
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }
        
        .quick-btn {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            color: #333;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .quick-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="admin-nav">
                <div class="admin-logo">⚙️ Admin Panel</div>
                <div class="admin-links">
                    <a href="admin_dashboard.php" class="active">Dashboard</a>
                    <a href="admin_products.php">Products</a>
                    <a href="admin_orders.php">Orders</a>
                    <a href="admin_users.php">Users</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">Admin Dashboard</h1>
                <p class="welcome-subtitle">Welcome back, Administrator! Here's your store overview.</p>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            
            <!-- Main Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="dashboard-section">
                    <h2 class="section-title">📋 Recent Orders</h2>
                    
                    <?php if(empty($recent_orders)): ?>
                        <div class="empty-state">
                            <p>No orders yet</p>
                        </div>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge" style="background: <?php echo getStatusBadge($order['order_status']); ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_order_details.php?order_id=<?php echo $order['id']; ?>" class="action-btn btn-small">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="admin_orders.php" class="action-btn">View All Orders</a>
                    </div>
                </div>
                
                <!-- Low Stock & Quick Actions -->
                <div style="display: flex; flex-direction: column; gap: 25px;">
                    <!-- Low Stock Alert -->
                    <div class="dashboard-section">
                        <h2 class="section-title">⚠️ Low Stock</h2>
                        
                        <?php if(empty($low_stock)): ?>
                            <div class="empty-state">
                                <p>All products are well stocked</p>
                            </div>
                        <?php else: ?>
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($low_stock as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td>
                                                <span style="color: #e74c3c; font-weight: bold;">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="admin_products.php?edit=<?php echo $product['id']; ?>" class="action-btn btn-small">
                                                    Restock
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="dashboard-section">
                        <h2 class="section-title">🚀 Quick Actions</h2>
                        <div class="quick-actions">
                            <a href="admin_products.php?action=add" class="quick-btn">Add Product</a>
                            <a href="admin_orders.php" class="quick-btn">Manage Orders</a>
                            <a href="admin_users.php" class="quick-btn">View Users</a>
                            <a href="admin_reports.php" class="quick-btn">Reports</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>