<?php
// debug_notifications.php
session_start();
include 'config.php';

echo "<h1>Notification System Debug</h1>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>ERROR: Not logged in!</p>";
    echo "<p>Please <a href='login.php'>login</a> first.</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p>User ID: {$user_id}</p>";

// Check database connection
try {
    $conn = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Check if notifications table exists
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'")->fetch();
    if ($table_check) {
        echo "<p style='color: green;'>✓ Notifications table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Notifications table DOES NOT exist</p>";
        
        
        try {
            $conn->exec($sql);
            echo "<p style='color: green;'>✓ Created notifications table</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Failed to create table: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking table: " . $e->getMessage() . "</p>";
}

// Check if there are any notifications for this user
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];
    
    echo "<p>Total notifications in database: {$count}</p>";
    
    if ($count == 0) {
        echo "<p style='color: orange;'>No notifications found. Adding sample notifications...</p>";
        
        // Add some sample notifications
        $sample_notifications = [
            ['Welcome to TechShop', 'Thank you for joining TechShop!', 'success', 'fa-user-plus', 'welcome'],
            ['Special Offer', 'Get 20% off on all smartphones this weekend.', 'warning', 'fa-tags', 'offer'],
            ['New Products', 'Check out our latest iPhone 16 Pro.', 'info', 'fa-mobile-alt', 'product']
        ];
        
        $added = 0;
        foreach ($sample_notifications as $notification) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, icon, category, is_read)
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            if ($stmt->execute([$user_id, $notification[0], $notification[1], $notification[2], $notification[3], $notification[4]])) {
                $added++;
            }
        }
        
        echo "<p style='color: green;'>✓ Added {$added} sample notifications</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking notifications: " . $e->getMessage() . "</p>";
}

// Test the functions
echo "<h2>Testing Functions</h2>";

// Test getUserNotifications
echo "<h3>getUserNotifications()</h3>";
$notifications = getUserNotifications($user_id, 5);
echo "<pre>" . print_r($notifications, true) . "</pre>";

// Test getUnreadNotificationCount
echo "<h3>getUnreadNotificationCount()</h3>";
$unread_count = getUnreadNotificationCount($user_id);
echo "<p>Unread count: {$unread_count}</p>";

// Test getting notifications by category
echo "<h3>Notifications by Category</h3>";
try {
    $stmt = $conn->prepare("SELECT category, COUNT(*) as count, SUM(is_read = 0) as unread FROM notifications WHERE user_id = ? GROUP BY category");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Category</th><th>Total</th><th>Unread</th></tr>";
    foreach ($categories as $cat) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($cat['category']) . "</td>";
        echo "<td>" . $cat['count'] . "</td>";
        echo "<td>" . $cat['unread'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Add a test cart notification
echo "<h3>Testing addCartNotification()</h3>";
if (addCartNotification($user_id, "Test Product", "99.99")) {
    echo "<p style='color: green;'>✓ Added cart notification successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to add cart notification</p>";
}

// Show all notifications
echo "<h2>All Notifications in Database</h2>";
try {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $all_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all_notifications)) {
        echo "<p>No notifications found</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Category</th><th>Read</th><th>Created</th></tr>";
        foreach ($all_notifications as $notification) {
            echo "<tr>";
            echo "<td>" . $notification['id'] . "</td>";
            echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($notification['message'], 0, 50)) . "...</td>";
            echo "<td>" . $notification['type'] . "</td>";
            echo "<td>" . $notification['category'] . "</td>";
            echo "<td>" . ($notification['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $notification['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='notifications.php'>Go to Notifications Page</a></p>";
echo "<p><a href='products.php'>Go to Products</a></p>";
?>