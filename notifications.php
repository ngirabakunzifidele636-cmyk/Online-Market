<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// ===== HANDLE ACTIONS =====

// Mark notification as read
if (isset($_GET['mark_as_read'])) {
    $notification_id = (int)$_GET['mark_as_read'];
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        $_SESSION['success_message'] = "Notification marked as read!";
        header("Location: notifications.php");
        exit();
    } catch (PDOException $e) {
        error_log("Failed to mark notification as read: " . $e->getMessage());
        $error = "Failed to mark notification as read";
    }
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $_SESSION['success_message'] = "All notifications marked as read!";
        header("Location: notifications.php");
        exit();
    } catch (PDOException $e) {
        error_log("Failed to mark all as read: " . $e->getMessage());
        $error = "Failed to mark all as read";
    }
}

// Delete notification
if (isset($_GET['delete'])) {
    $notification_id = (int)$_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        $_SESSION['success_message'] = "Notification deleted!";
        header("Location: notifications.php");
        exit();
    } catch (PDOException $e) {
        error_log("Failed to delete notification: " . $e->getMessage());
        $error = "Failed to delete notification";
    }
}

// Clear all notifications
if (isset($_POST['clear_all'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success_message'] = "All notifications cleared!";
        header("Location: notifications.php");
        exit();
    } catch (PDOException $e) {
        error_log("Failed to clear notifications: " . $e->getMessage());
        $error = "Failed to clear all notifications";
    }
}

// Display success message if exists
if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// ===== GET NOTIFICATIONS =====

// Get all notifications for this user
try {
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $all_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to get notifications: " . $e->getMessage());
    $all_notifications = [];
}

// Get unread count
$unread_count = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Failed to get unread count: " . $e->getMessage());
}

// Calculate statistics
$total_count = count($all_notifications);
$orders_count = 0;
$offers_count = 0;
$system_count = 0;

foreach ($all_notifications as $notification) {
    $category = strtolower($notification['category'] ?? 'system');
    if ($category == 'order' || $category == 'orders') {
        $orders_count++;
    } elseif ($category == 'offer' || $category == 'offers') {
        $offers_count++;
    } else {
        $system_count++;
    }
}

// Function to get time elapsed
function time_elapsed_string($datetime) {
    if (empty($datetime)) return 'Just now';
    
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minute' . (floor($diff / 60) > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hour' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' month' . (floor($diff / 2592000) > 1 ? 's' : '') . ' ago';
    } else {
        return floor($diff / 31536000) . ' year' . (floor($diff / 31536000) > 1 ? 's' : '') . ' ago';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - TechStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .notifications-container {
            background: white;
            border-radius: 20px;
            margin: 30px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 900px;
            overflow: hidden;
        }
        
        .notifications-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }
        
        .notifications-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .notifications-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .notification-content-wrapper {
            padding: 30px 40px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stat-card .label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .stat-card.primary .number { color: #667eea; }
        .stat-card.warning .number { color: #ffc107; }
        .stat-card.success .number { color: #28a745; }
        .stat-card.info .number { color: #17a2b8; }
        
        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
            border-left: 4px solid #dee2e6;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .notification-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transform: translateX(5px);
        }
        
        .notification-item.unread {
            border-left-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .notification-item .icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .notification-item .icon.success { background: #d4edda; color: #28a745; }
        .notification-item .icon.warning { background: #fff3cd; color: #ffc107; }
        .notification-item .icon.info { background: #d1ecf1; color: #17a2b8; }
        .notification-item .icon.primary { background: #cfe2ff; color: #007bff; }
        .notification-item .icon.danger { background: #f8d7da; color: #dc3545; }
        
        .notification-item .content {
            flex: 1;
        }
        
        .notification-item .content .title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .notification-item .content .message {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }
        
        .notification-item .content .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .notification-item .content .meta .time {
            color: #999;
            font-size: 0.8rem;
        }
        
        .notification-item .content .meta .badge-category {
            background: #e9ecef;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .notification-item .content .meta .badge-unread {
            background: #dc3545;
            color: white;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .notification-item .actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }
        
        .notification-item .actions .btn-sm {
            padding: 4px 12px;
            font-size: 0.8rem;
            border-radius: 20px;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-custom-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }
        
        .btn-custom-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .notification-content-wrapper {
                padding: 20px;
            }
            
            .notifications-header {
                padding: 20px;
            }
            
            .notification-item {
                flex-direction: column;
            }
            
            .notification-item .actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="notifications-container">
            <div class="notifications-header">
                <h1><i class="fas fa-bell me-2"></i>Notifications</h1>
                <p>You have <strong><?php echo $unread_count; ?></strong> unread notification<?php echo $unread_count != 1 ? 's' : ''; ?></p>
                
                <div class="header-actions">
                    <?php if ($unread_count > 0): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="mark_all_read" class="btn-custom btn-custom-success" style="padding: 8px 20px; font-size: 0.9rem;">
                                <i class="fas fa-check-double"></i> Mark All as Read
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (!empty($all_notifications)): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all notifications? This action cannot be undone.')">
                            <button type="submit" name="clear_all" class="btn-custom btn-custom-danger" style="padding: 8px 20px; font-size: 0.9rem;">
                                <i class="fas fa-trash"></i> Clear All
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="notification-content-wrapper">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="number"><?php echo $total_count; ?></div>
                        <div class="label">Total</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="number"><?php echo $unread_count; ?></div>
                        <div class="label">Unread</div>
                    </div>
                    <div class="stat-card success">
                        <div class="number"><?php echo $orders_count; ?></div>
                        <div class="label">Orders</div>
                    </div>
                    <div class="stat-card info">
                        <div class="number"><?php echo $offers_count; ?></div>
                        <div class="label">Offers</div>
                    </div>
                </div>
                
                <!-- Notifications List -->
                <?php if (empty($all_notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No Notifications</h3>
                        <p>You're all caught up! No new notifications.</p>
                        <a href="products.php" class="btn-custom mt-3">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_notifications as $notification): 
                        $is_unread = $notification['is_read'] == 0;
                        $icon_type = $notification['type'] ?? 'info';
                        $icon_class = !empty($notification['icon']) ? $notification['icon'] : 'fa-bell';
                        $action_url = !empty($notification['action_url']) ? htmlspecialchars($notification['action_url']) : '#';
                    ?>
                        <div class="notification-item <?php echo $is_unread ? 'unread' : ''; ?>"
                             onclick="handleNotificationClick(<?php echo (int)$notification['id']; ?>, '<?php echo addslashes($action_url); ?>')">
                            
                            <div class="icon <?php echo $icon_type; ?>">
                                <i class="fas <?php echo $icon_class; ?>"></i>
                            </div>
                            
                            <div class="content">
                                <div class="title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div class="message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                
                                <div class="meta">
                                    <span class="time">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo time_elapsed_string($notification['created_at']); ?>
                                    </span>
                                    <div>
                                        <span class="badge-category"><?php echo ucfirst($notification['category'] ?? 'System'); ?></span>
                                        <?php if ($is_unread): ?>
                                            <span class="badge-unread">Unread</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="actions">
                                <?php if ($is_unread): ?>
                                    <a href="notifications.php?mark_as_read=<?php echo (int)$notification['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       onclick="event.stopPropagation();">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="notifications.php?delete=<?php echo (int)$notification['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="event.stopPropagation(); return confirm('Delete this notification?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                
                                <?php if (!empty($notification['action_url']) && $notification['action_url'] != '#'): ?>
                                    <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                                       class="btn btn-sm btn-primary" 
                                       onclick="event.stopPropagation();">
                                        <i class="fas fa-external-link-alt"></i> View
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleNotificationClick(notificationId, actionUrl) {
            // Mark as read
            fetch('notifications.php?mark_as_read=' + notificationId, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(data => {
                if (actionUrl && actionUrl !== '#') {
                    window.location.href = actionUrl;
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (actionUrl && actionUrl !== '#') {
                    window.location.href = actionUrl;
                } else {
                    window.location.reload();
                }
            });
        }
        
        // Auto-dismiss alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>