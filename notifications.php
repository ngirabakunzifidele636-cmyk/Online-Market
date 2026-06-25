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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_all_read'])) {
        if (markAllNotificationsAsRead($user_id)) {
            $_SESSION['success_message'] = "All notifications marked as read!";
            header("Location: notifications.php");
            exit();
        } else {
            $error = "Failed to mark all as read";
        }
    }
    
    if (isset($_POST['clear_all'])) {
        if (clearAllNotifications($user_id)) {
            $_SESSION['success_message'] = "All notifications cleared!";
            header("Location: notifications.php");
            exit();
        } else {
            $error = "Failed to clear all notifications";
        }
    }
}

if (isset($_GET['mark_as_read'])) {
    $notification_id = $_GET['mark_as_read'];
    if (markNotificationAsRead($notification_id, $user_id)) {
        $_SESSION['success_message'] = "Notification marked as read!";
        header("Location: notifications.php");
        exit();
    } else {
        $error = "Failed to mark notification as read";
    }
}

if (isset($_GET['delete'])) {
    $notification_id = $_GET['delete'];
    if (deleteNotification($notification_id, $user_id)) {
        $_SESSION['success_message'] = "Notification deleted!";
        header("Location: notifications.php");
        exit();
    } else {
        $error = "Failed to delete notification";
    }
}

// Display success message if exists
if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get all notifications from database
$all_notifications = getUserNotifications($user_id, 50);

// Get statistics from database
$total_count = count($all_notifications);
$unread_count = getUnreadNotificationCount($user_id);

// Get counts by category
$orders_count = 0;
$offers_count = 0;
$cart_count = 0;
$system_count = 0;

foreach ($all_notifications as $notification) {
    switch ($notification['category']) {
        case 'orders':
        case 'order':
            $orders_count++;
            break;
        case 'offers':
        case 'offer':
            $offers_count++;
            break;
        case 'cart':
            $cart_count++;
            break;
        case 'system':
        case 'welcome':
        case 'product':
            $system_count++;
            break;
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
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .notification-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .notification-card.unread {
            border-left-color: #007bff;
            background-color: #f8f9fa;
        }
        .notification-success {
            border-left-color: #28a745 !important;
        }
        .notification-warning {
            border-left-color: #ffc107 !important;
        }
        .notification-info {
            border-left-color: #17a2b8 !important;
        }
        .notification-primary {
            border-left-color: #007bff !important;
        }
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
        }
        .badge-unread {
            background-color: #dc3545;
        }
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        .stat-card {
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <div class="btn-group">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="mark_all_read" class="btn btn-success">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </button>
                </form>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all notifications?')">
                    <button type="submit" name="clear_all" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </form>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total</h6>
                                <h2 class="mb-0"><?php echo $total_count; ?></h2>
                            </div>
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Unread</h6>
                                <h2 class="mb-0"><?php echo $unread_count; ?></h2>
                            </div>
                            <i class="fas fa-envelope fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Orders</h6>
                                <h2 class="mb-0"><?php echo $orders_count; ?></h2>
                            </div>
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Offers</h6>
                                <h2 class="mb-0"><?php echo $offers_count; ?></h2>
                            </div>
                            <i class="fas fa-tag fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notifications List -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($all_notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No Notifications</h3>
                        <p>You're all caught up! No new notifications.</p>
                        <a href="products.php" class="btn btn-primary mt-3">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_notifications as $notification): 
                        $unread_class = $notification['is_read'] == 0 ? 'unread' : '';
                        $type_class = 'notification-' . $notification['type'];
                    ?>
                        <div class="notification-card card mb-3 <?php echo $unread_class; ?> <?php echo $type_class; ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon notification-<?php echo $notification['type']; ?> me-3">
                                        <i class="fas <?php echo $notification['icon']; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($notification['title']); ?></h5>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($notification['is_read'] == 0): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="notifications.php?mark_as_read=<?php echo $notification['id']; ?>">
                                                            <i class="fas fa-check me-2"></i> Mark as Read
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="notifications.php?delete=<?php echo $notification['id']; ?>" onclick="return confirm('Delete this notification?')">
                                                            <i class="fas fa-trash me-2"></i> Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="far fa-clock"></i> <?php echo time_elapsed_string($notification['created_at']); ?>
                                                <span class="badge bg-secondary ms-2"><?php echo ucfirst($notification['category']); ?></span>
                                            </small>
                                            <div>
                                                <?php if ($notification['is_read'] == 0): ?>
                                                    <span class="badge badge-unread me-2">Unread</span>
                                                <?php endif; ?>
                                                <?php if (!empty($notification['action_url'])): ?>
                                                    <a href="<?php echo $notification['action_url']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-external-link-alt"></i> View Details
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>TechStore</h5>
                    <p>Stay updated with all your orders and offers.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; 2025 TechStore. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
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