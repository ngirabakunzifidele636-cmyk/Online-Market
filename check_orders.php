<?php
include 'config.php';

echo "<h3>Orders System Check</h3>";

// Check if orders table exists
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'orders'");
    $orders_table_exists = $stmt->fetch();
    
    echo "Orders table exists: " . ($orders_table_exists ? 'YES' : 'NO') . "<br>";
    
    if ($orders_table_exists) {
        // Check orders count
        $stmt = $conn->query("SELECT COUNT(*) as order_count FROM orders");
        $order_count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total orders in database: " . $order_count['order_count'] . "<br>";
        
        // Check if current user has orders
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT COUNT(*) as user_orders FROM orders WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_order_count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Your orders: " . $user_order_count['user_orders'] . "<br>";
            
            // Show your orders
            $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($orders) > 0) {
                echo "<h4>Your Orders:</h4>";
                foreach ($orders as $order) {
                    echo "Order #" . $order['id'] . " - Total: $" . $order['total_amount'] . " - Status: " . $order['status'] . "<br>";
                }
            } else {
                echo "<p>No orders found for your account.</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<hr>";
echo "<a href='setup_orders.php'>Setup Orders System</a> | ";
echo "<a href='create_test_order.php'>Create Test Order</a> | ";
echo "<a href='profile.php'>Back to Profile</a>";
?>