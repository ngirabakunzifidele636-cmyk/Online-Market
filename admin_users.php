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
    
    // Handle user actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_user_status'])) {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$_POST['is_active'], $_POST['user_id']]);
            $success = "User status updated successfully!";
        }
    }
    
    // Get all users with order counts
    $users = $pdo->query("
        SELECT u.*, 
               COUNT(o.id) as order_count,
               COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
        
        .users-table { width: 100%; border-collapse: collapse; }
        .users-table th, .users-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        .users-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white; }
        .status-active { background: #27ae60; }
        .status-inactive { background: #95a5a6; }
        .verified-badge { background: #3498db; color: white; padding: 2px 6px; border-radius: 8px; font-size: 10px; }
        
        .btn { background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn:hover { background: #5a6fd8; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219653; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .user-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; font-size: 12px; }
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
                    <a href="admin_users.php" class="active">Users</a>
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
            
            <!-- User Statistics -->
            <div class="user-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $active_users = array_filter($users, function($user) { return $user['is_active']; });
                        echo count($active_users);
                        ?>
                    </div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $verified_users = array_filter($users, function($user) { return $user['email_verified']; });
                        echo count($verified_users);
                        ?>
                    </div>
                    <div class="stat-label">Verified Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $users_with_orders = array_filter($users, function($user) { return $user['order_count'] > 0; });
                        echo count($users_with_orders);
                        ?>
                    </div>
                    <div class="stat-label">Users with Orders</div>
                </div>
            </div>
            
            <!-- Users List -->
            <div class="admin-section">
                <h2 class="section-title">👥 User Management (<?php echo count($users); ?> users)</h2>
                
                <?php if(empty($users)): ?>
                    <p>No users found.</p>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Member Since</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <?php if($user['first_name'] || $user['last_name']): ?>
                                            <br><small><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></small>
                                        <?php endif; ?>
                                        <?php if($user['email_verified']): ?>
                                            <br><span class="verified-badge">✓ Verified</span>
                                        <?php else: ?>
                                            <br><span style="color: #95a5a6; font-size: 11px;">Unverified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($user['email']); ?>
                                        <?php if($user['phone']): ?>
                                            <br><small><?php echo htmlspecialchars($user['phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        <br><small style="color: #666;"><?php echo time_ago($user['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <?php if($user['order_count'] > 0): ?>
                                            <strong><?php echo $user['order_count']; ?></strong> order(s)
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">No orders</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($user['total_spent'] > 0): ?>
                                            $<?php echo number_format($user['total_spent'], 2); ?>
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">$0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <?php if($user['is_active']): ?>
                                                <input type="hidden" name="is_active" value="0">
                                                <button type="submit" name="update_user_status" class="btn btn-warning" 
                                                        onclick="return confirm('Deactivate this user? They will not be able to login.')">
                                                    Deactivate
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="is_active" value="1">
                                                <button type="submit" name="update_user_status" class="btn btn-success">
                                                    Activate
                                                </button>
                                            <?php endif; ?>
                                        </form>
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

<?php
// Helper function to show time ago
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return floor($diff / 2592000) . ' months ago';
    }
}
?>