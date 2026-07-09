<?php 
include 'config.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $debug_stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $debug_stmt->execute([$user_id]);
    $debug_user = $debug_stmt->fetch();
    echo "<!-- DEBUG: Fresh profile_image from DB: " . ($debug_user['profile_image'] ?? 'NULL') . " -->";
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error_message'] = "User not found!";
        header('Location: index.php');
        exit();
    }

    // Get user's orders count
    $order_count = 0;
    $pending_count = 0;
    $recent_orders = [];
    
    try {
        $order_stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
        $order_stmt->execute([$user_id]);
        $order_data = $order_stmt->fetch(PDO::FETCH_ASSOC);
        $order_count = $order_data ? $order_data['order_count'] : 0;

        // Get pending orders count
        $pending_stmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM orders WHERE user_id = ? AND status = 'pending'");
        $pending_stmt->execute([$user_id]);
        $pending_data = $pending_stmt->fetch(PDO::FETCH_ASSOC);
        $pending_count = $pending_data ? $pending_data['pending_count'] : 0;

        // Get recent orders
        $recent_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
        $recent_stmt->execute([$user_id]);
        $recent_orders = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Orders table might not exist
        error_log("Orders error: " . $e->getMessage());
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Function to get status badge class
function getStatusBadge($status) {
    switch ($status) {
        case 'pending': return 'bg-warning';
        case 'processing': return 'bg-info';
        case 'shipped': return 'bg-primary';
        case 'delivered': return 'bg-success';
        case 'cancelled': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .order-item {
            border-left: 4px solid #007bff;
            padding-left: 15px;
        }
    </style>
</head>
<body>
     <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <?php include 'header.php'; ?>
    
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php
                    $profile_image_src = 'https://via.placeholder.com/120?text=TechShop';
                    if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                        $profile_image_src = $user['profile_image'];
                    }
                    ?>
                    <img src="<?= htmlspecialchars($profile_image_src) ?>" 
                         alt="Profile Image" 
                         class="profile-pic"
                         onerror="this.src='https://via.placeholder.com/120?text=TechShop'">
                </div>
                <div class="col-md-10">
                    <h1><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h1>
                    <p class="mb-1">@<?= htmlspecialchars($user['username']) ?></p>
                    <p class="mb-0"><?= htmlspecialchars($user['email']) ?></p>
                    <!-- DEBUG -->
                    <small style="color: yellow; background: rgba(0,0,0,0.5); padding: 2px 5px; border-radius: 3px; margin-top: 5px; display: inline-block;">
                        Image: <?= !empty($user['profile_image']) ? 'SET (' . basename($user['profile_image']) . ')' : 'NOT SET' ?>
                    </small>
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
        
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $order_count ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $pending_count ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number">10</div>
                    <div class="stat-label">Wishlist Items</div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-user-circle me-2"></i>Profile Information</h5>
                        <div>
                            <a href="profile_edit.php" class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-edit me-1"></i>Edit Profile
                            </a>
                            <a href="change_password.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-key me-1"></i>Change Password
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-user me-2"></i>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                                <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                <p><strong><i class="fas fa-calendar me-2"></i>Member since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-id-card me-2"></i>Full Name:</strong> <?= htmlspecialchars($user['full_name'] ?? 'Not set') ?></p>
                                <p><strong><i class="fas fa-phone me-2"></i>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
                                <p><strong><i class="fas fa-clock me-2"></i>Last Updated:</strong> <?= date('F j, Y g:i A', strtotime($user['updated_at'])) ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($user['address'])): ?>
                            <hr>
                            <p><strong><i class="fas fa-map-marker-alt me-2"></i>Address:</strong></p>
                            <p><?= nl2br(htmlspecialchars($user['address'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RECENT ORDERS SECTION - THIS WAS MISSING -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-shopping-bag me-2"></i>Recent Orders</h5>
                        <?php if ($order_count > 0): ?>
                            <a href="order_details.php" class="btn btn-outline-primary btn-sm">View All Orders</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($order_count > 0 && !empty($recent_orders)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_orders as $order): ?>
                                    <div class="list-group-item order-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Order #<?= htmlspecialchars($order['order_number']) ?></h6>
                                                <p class="mb-1">Total: $<?= number_format($order['total_amount'], 2) ?></p>
                                                <small class="text-muted">Placed on: <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge <?= getStatusBadge($order['status']) ?>"><?= ucfirst($order['status']) ?></span>
                                                <div class="mt-2">
                                                    <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($order_count > 3): ?>
                                <div class="mt-3 text-center">
                                    <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                    <!-- <a href="order_details.php?order_id=<?= $order['id'] ?>">View Details</a>  -->
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">No orders yet. 
                                <a href="products.php" class="btn btn-primary btn-sm ms-2">Start Shopping</a>
                                <?php if (file_exists('create_test_order.php')): ?>
                                    <a href="create_test_order.php" class="btn btn-outline-secondary btn-sm ms-1">Create Test Order</a>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'footer.php'?>