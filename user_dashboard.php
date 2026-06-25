<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get user's orders
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $user_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <h2>My Orders</h2>
        
        <?php if (!empty($user_orders)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_number']) ?></td>
                                <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $order['order_status'] == 'delivered' ? 'success' : 
                                        ($order['order_status'] == 'processing' ? 'primary' : 
                                        ($order['order_status'] == 'pending' ? 'warning' : 'info')) 
                                    ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't placed any orders yet.
                <a href="products.php" class="alert-link">Start shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>