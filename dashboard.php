<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current user data to check admin status
try {
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['is_admin'] != 1) {
        $_SESSION['error_message'] = "Access denied. Admin privileges required.";
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

try {
    // Get statistics
    $users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC)['count'];
    $orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch(PDO::FETCH_ASSOC)['count'];
    $products_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate total revenue
    $revenue_stmt = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE payment_status = 'paid'");
    $revenue_data = $revenue_stmt->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $revenue_data['total_revenue'] ?? 0;
    
    // Get recent orders with user info
    $recent_orders = $conn->query("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent users
    $recent_users = $conn->query("
        SELECT id, username, email, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        .users-icon { color: #007bff; }
        .orders-icon { color: #28a745; }
        .products-icon { color: #dc3545; }
        .revenue-icon { color: #ffc107; }
        
        .order-actions {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
                    <p class="mb-0">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>!</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-success fs-6">Administrator</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php $_SESSION['success_message'] = ''; ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php $_SESSION['error_message'] = ''; ?>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon users-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?= $users_count ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orders-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-number"><?= $orders_count ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon products-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-number"><?= $products_count ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon revenue-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number">$<?= number_format($total_revenue, 2) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Orders -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Recent Orders</h5>
                        <a href="admin_orders.php" class="btn btn-sm btn-outline-primary">Manage All Orders</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?= $order['order_number'] ?></td>
                                                <td><?= htmlspecialchars($order['username']) ?></td>
                                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                                <td>
                                                    <span class="badge 
                                                        <?= $order['status'] == 'delivered' ? 'bg-success' : '' ?>
                                                        <?= $order['status'] == 'processing' ? 'bg-primary' : '' ?>
                                                        <?= $order['status'] == 'pending' ? 'bg-warning' : '' ?>
                                                        <?= $order['status'] == 'shipped' ? 'bg-info' : '' ?>
                                                    "><?= ucfirst($order['status']) ?></span>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <div class="order-actions">
                                                        <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="delete_order.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this order?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent orders.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i>Recent Users</h5>
                        <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_users)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($user['username']) ?></h6>
                                                <p class="mb-1 text-muted"><?= htmlspecialchars($user['email']) ?></p>
                                                <small class="text-muted">Joined: <?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                                            </div>
                                            <span class="badge bg-secondary">ID: <?= $user['id'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent users.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="products.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-box me-2"></i>Manage Products
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="admin_orders.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Manage Orders
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="users.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-users me-2"></i>Manage Users
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="create_test_order.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-plus me-2"></i>Create Test Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>