<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config if not already included
if (!isset($conn)) {
    include 'config.php';
}

// Get cart count from database if user is logged in
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $cart_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $cart_stmt->execute([$_SESSION['user_id']]);
        $cart_result = $cart_stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $cart_result['total'] ?? 0;
    } catch (PDOException $e) {
        // Fallback to session cart
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $cart_count += $item['quantity'];
            }
        }
    }
} else {
    // Fallback to session cart for guests
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['quantity'];
        }
    }
}

// Initialize notifications array
$notifications = [];
$notification_count = 0;

// Get REAL notifications from database ONLY for logged-in users
if (isset($_SESSION['user_id'])) {
    try {
        // Check if notifications table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($table_check->rowCount() > 0) {
            // Get user's notifications from database
            $notif_stmt = $conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $notif_stmt->execute([$_SESSION['user_id']]);
            $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Count unread notifications
            $count_stmt = $conn->prepare("
                SELECT COUNT(*) as total FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $count_stmt->execute([$_SESSION['user_id']]);
            $notification_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        }
    } catch (PDOException $e) {
        error_log("Notifications error: " . $e->getMessage());
    }
}

// Format notification time
function formatNotificationTime($timestamp) {
    $now = time();
    $time = strtotime($timestamp);
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

// Get notification icon based on type
function getNotificationIcon($type) {
    $icons = [
        'order' => 'fa-shopping-bag',
        'payment' => 'fa-credit-card',
        'shipping' => 'fa-truck',
        'delivery' => 'fa-box-open',
        'promo' => 'fa-tags',
        'warning' => 'fa-exclamation-triangle',
        'success' => 'fa-check-circle',
        'info' => 'fa-info-circle'
    ];
    return $icons[$type] ?? 'fa-bell';
}

// Get notification color based on type
function getNotificationType($type) {
    $colors = [
        'order' => 'primary',
        'payment' => 'success',
        'shipping' => 'info',
        'delivery' => 'success',
        'promo' => 'warning',
        'warning' => 'danger',
        'success' => 'success',
        'info' => 'info'
    ];
    return $colors[$type] ?? 'primary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechShop - Your Gadget Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        
        .navbar-nav .nav-link.active {
            font-weight: bold;
            color: #007bff !important;
            border-bottom: 2px solid #007bff;
        }
        .badge-cart {
            background-color: #dc3545;
            font-size: 0.7em;
        }
        .badge-notification {
            background-color: #ffc107;
            font-size: 0.6em;
            color: #000;
        }
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #007bff;
        }
        .nav-item.dropdown.show .nav-link,
        .nav-item.dropdown:hover .nav-link {
            color: #007bff !important;
        }
        .nav-item.dropdown.active .nav-link {
            color: #007bff !important;
            font-weight: bold;
            border-bottom: 2px solid #007bff;
        }
        .search-form {
            min-width: 250px;
        }
        .search-form .input-group {
            border-radius: 20px;
            overflow: hidden;
        }
        .search-form .form-control {
            border: 1px solid #dee2e6;
            border-right: none;
            padding-left: 15px;
        }
        .search-form .btn-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 8px 20px;
        }
        .search-form .btn-search:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .notification-dropdown {
            min-width: 350px;
            max-height: 450px;
            overflow-y: auto;
        }
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            background-color: #f0f7ff;
            border-left: 3px solid #007bff;
        }
        .notification-time {
            font-size: 0.75em;
            color: #6c757d;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .notification-success {
            background-color: #d4edda;
            color: #155724;
        }
        .notification-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .notification-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .notification-primary {
            background-color: #cce5ff;
            color: #004085;
        }
        .notification-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        .notification-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 3px;
            white-space: normal;
            word-wrap: break-word;
        }
        .notification-message {
            font-size: 0.8rem;
            color: #495057;
            margin-bottom: 3px;
            white-space: normal;
            word-wrap: break-word;
        }
        @media (max-width: 992px) {
            .search-form {
                margin: 10px 0;
                min-width: 100%;
            }
            .notification-dropdown {
                min-width: 300px;
            }
        }
        body{
    /* padding-top: 85px; */
}

.navbar{
    /* background: linear-gradient(135deg, #0f172a, #1e293b) !important; */
    padding: 12px 10px;
}

.navbar-brand{
    font-size: 1.7rem;
    font-weight: bold;
}

.navbar-nav .nav-link{
    color: #fff !important;
    margin: 0 8px;
    transition: all 0.3s ease;
}

.navbar-nav .nav-link:hover{
    color: #0d6efd !important;
    transform: translateY(-2px);
}

.dropdown-menu{
    border: none;
    border-radius: 10px;
}

.search-form .form-control{s
    border-radius: 25px 0 0 25px;
}

.search-form .btn-search{
    border-radius: 0 25px 25px 0;
}

.badge-cart,
.badge-notification{
    border-radius: 50%;
}
    </style>
</head>
<body>
    
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item dropdown <?php 
                        $current_page = basename($_SERVER['PHP_SELF']);
                        $product_pages = ['products.php', 'smartphones.php', 'smartwatch.php', 'laptops.php', 'headphones.php'];
                        echo in_array($current_page, $product_pages) ? 'active' : '';
                    ?>">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-shopping-bag"></i> Products
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item <?php echo $current_page == 'products.php' ? 'active' : ''; ?>" href="products.php"><i class="fas fa-list me-2"></i> All Products</a></li>
                            <li><a class="dropdown-item <?php echo $current_page == 'smartphones.php' ? 'active' : ''; ?>" href="smartphones.php"><i class="fas fa-mobile-alt me-2"></i> Smart Phones</a></li>
                            <li><a class="dropdown-item <?php echo $current_page == 'smartwatch.php' ? 'active' : ''; ?>" href="smartwatch.php"><i class="fas fa-clock me-2"></i> Smart Watch</a></li>
                            <li><a class="dropdown-item <?php echo $current_page == 'laptops.php' ? 'active' : ''; ?>" href="laptops.php"><i class="fas fa-laptop me-2"></i> Laptop</a></li>
                            <li><a class="dropdown-item <?php echo $current_page == 'headphones.php' ? 'active' : ''; ?>" href="headphones.php"><i class="fas fa-headphones me-2"></i> Head Phones</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>" href="about.php">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                    </li>
                </ul>
                
                <!-- Search Bar -->
                <form class="d-flex search-form me-3" action="search.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Search products..." aria-label="Search">
                        <button class="btn btn-search" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <ul class="navbar-nav">
                    <!-- Notification Bell - Only show for logged in users -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="notificationDropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge badge-notification">
                                    <?php echo $notification_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                            <li>
                                <div class="dropdown-header d-flex justify-content-between align-items-center bg-light">
                                    <strong><i class="fas fa-bell me-2"></i>Notifications</strong>
                                    <?php if ($notification_count > 0): ?>
                                        <span class="badge bg-warning"><?php echo $notification_count; ?> new</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notification): 
                                    $unread_class = isset($notification['is_read']) && !$notification['is_read'] ? 'unread' : '';
                                    $type = $notification['type'] ?? 'info';
                                    $icon = $notification['icon'] ?? getNotificationIcon($type);
                                    $type_class = getNotificationType($type);
                                    $formatted_time = formatNotificationTime($notification['created_at'] ?? date('Y-m-d H:i:s'));
                                ?>
                                    <li>
                                        <a href="notification.php?id=<?php echo $notification['id']; ?>" class="notification-item <?php echo $unread_class; ?>" data-id="<?php echo $notification['id']; ?>">
                                            <div class="d-flex align-items-start">
                                                <div class="notification-icon notification-<?php echo $type_class; ?>">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                    <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                                    <small class="notification-time">
                                                        <i class="far fa-clock me-1"></i><?php echo $formatted_time; ?>
                                                    </small>
                                                </div>
                                                <?php if (isset($notification['is_read']) && !$notification['is_read']): ?>
                                                    <span class="badge bg-warning rounded-pill ms-2 align-self-center">New</span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>
                                    <div class="text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-bell-slash fa-3x text-muted"></i>
                                        </div>
                                        <h6 class="text-muted mb-2">No notifications yet</h6>
                                        <p class="small text-muted mb-0">When you get notifications, they'll appear here</p>
                                    </div>
                                </li>
                            <?php endif; ?>
                            
                            <?php if (!empty($notifications)): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-center text-primary" href="notifications.php">
                                        <i class="fas fa-eye me-1"></i> View All Notifications
                                    </a>
                                </li>
                                <?php if ($notification_count > 0): ?>
                                <li>
                                    <a class="dropdown-item text-center" href="#" id="markAllRead">
                                        <i class="fas fa-check-double me-1"></i> Mark All as Read
                                    </a>
                                </li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> 
                            <?php 
                            if (isset($_SESSION['username'])) {
                                echo htmlspecialchars($_SESSION['username']);
                            } elseif (isset($_SESSION['user']['name'])) {
                                echo htmlspecialchars($_SESSION['user']['name']);
                            } else {
                                echo "My Account";
                            }
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-history me-2"></i> My Orders</a></li>
                            <li><a class="dropdown-item" href="profile_edit.php"><i class="fas fa-edit me-2"></i> Edit Profile</a></li>
                            <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge badge-cart">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4">

<script>
// JavaScript for notification functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification system
    initNotificationSystem();
    
    // Monitor cart additions
    monitorCartAdditions();
});

function initNotificationSystem() {
    // Mark all notifications as read
    const markAllReadBtn = document.getElementById('markAllRead');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAllNotificationsAsRead();
        });
    }
    
    // Add click handlers for notification items
    document.querySelectorAll('.notification-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const title = this.querySelector('h6').textContent;
            const message = this.querySelector('p').textContent;
            
            // Mark as read when clicked
            if (this.classList.contains('unread')) {
                this.classList.remove('unread');
                const newBadge = this.querySelector('.badge.bg-warning');
                if (newBadge) {
                    newBadge.remove();
                }
                
                // Update notification count
                updateNotificationCount(-1);
            }
            
            // Show notification details
            showNotificationToast(`<strong>${title}</strong><br>${message}`, 'info');
        });
    });
    
    // Auto-hide notifications after 10 seconds
    setTimeout(function() {
        const notificationDropdown = document.querySelector('.notification-dropdown');
        if (notificationDropdown && notificationDropdown.classList.contains('show')) {
            const dropdownInstance = bootstrap.Dropdown.getInstance(document.querySelector('[data-bs-toggle="dropdown"]'));
            dropdownInstance?.hide();
        }
    }, 10000);
}

function markAllNotificationsAsRead() {
    // Remove "New" badges and unread styling
    document.querySelectorAll('.notification-item.unread').forEach(function(item) {
        item.classList.remove('unread');
    });
    
    // Remove new badges from notification items
    document.querySelectorAll('.notification-item .badge.bg-warning').forEach(function(badge) {
        badge.remove();
    });
    
    // Update notification count badge
    const notificationBadge = document.querySelector('.badge-notification');
    if (notificationBadge) {
        notificationBadge.remove();
    }
    
    // Update header count
    const headerBadge = document.querySelector('.notification-dropdown .badge.bg-warning');
    if (headerBadge) {
        headerBadge.remove();
    }
    
    // Show success message
    showNotificationToast('All notifications marked as read!', 'success');
    
    // In a real app, send AJAX request to update database
    // updateAllNotificationsAsRead();
}

function updateNotificationCount(change) {
    const notificationBadge = document.querySelector('.badge-notification');
    const headerBadge = document.querySelector('.notification-dropdown .badge.bg-warning');
    
    if (notificationBadge) {
        let currentCount = parseInt(notificationBadge.textContent);
        currentCount += change;
        
        if (currentCount <= 0) {
            notificationBadge.remove();
            if (headerBadge) headerBadge.remove();
        } else {
            notificationBadge.textContent = currentCount;
            if (headerBadge) headerBadge.textContent = currentCount + ' new';
        }
    }
}

function showNotificationToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Function to monitor cart additions
function monitorCartAdditions() {
    // Listen for form submissions (cart additions)
    document.querySelectorAll('form[action*=".php"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Check if this is an add to cart form
            const addToCartBtn = this.querySelector('button[name="add_to_cart"]');
            if (addToCartBtn) {
                // Get product details
                const productName = this.querySelector('input[name="product_name"]')?.value;
                const productPrice = this.querySelector('input[name="product_price"]')?.value;
                
                if (productName) {
                    // Store cart addition data for notification
                    sessionStorage.setItem('lastCartAddition', JSON.stringify({
                        name: productName,
                        price: productPrice,
                        time: new Date().toISOString()
                    }));
                }
            }
        });
    });
    
    // Check for recent cart addition when page loads
    const lastCartAddition = sessionStorage.getItem('lastCartAddition');
    if (lastCartAddition) {
        const addition = JSON.parse(lastCartAddition);
        const timeDiff = (new Date() - new Date(addition.time)) / 1000; // in seconds
        
        // Show notification if addition was recent (within last 10 seconds)
        if (timeDiff < 10) {
            // Add to notifications dropdown
            addCartNotification(addition.name, addition.price);
            
            // Clear the stored data
            sessionStorage.removeItem('lastCartAddition');
        }
    }
}

// Function to add cart notification to dropdown
function addCartNotification(productName, productPrice) {
    const notificationsDropdown = document.querySelector('.notification-dropdown');
    if (!notificationsDropdown) return;
    
    // Create new notification item
    const newNotification = document.createElement('li');
    newNotification.innerHTML = `
        <a href="#" class="dropdown-item notification-item unread">
            <div class="d-flex align-items-start">
                <div class="notification-icon notification-success">
                    <i class="fas fa-cart-plus"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">Added to Cart</h6>
                    <p class="mb-1 small">${productName} added to your cart</p>
                    <small class="notification-time">Just now</small>
                </div>
                <span class="badge bg-warning rounded-pill ms-2">New</span>
            </div>
        </a>
    `;
    
    // Add click handler
    newNotification.querySelector('.notification-item').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = 'cart.php';
    });
    
    // Insert after the divider (or at the beginning if no divider)
    const divider = notificationsDropdown.querySelector('.dropdown-divider');
    if (divider) {
        divider.parentNode.insertBefore(newNotification, divider.nextSibling);
    } else {
        const dropdownHeader = notificationsDropdown.querySelector('.dropdown-header');
        if (dropdownHeader) {
            dropdownHeader.parentNode.insertBefore(newNotification, dropdownHeader.nextSibling);
        }
    }
    
    // Update notification count
    updateNotificationCount(1);
    
    // Show toast notification
    showNotificationToast(`<strong>${productName}</strong> added to cart!`, 'success');
}

// Search form enhancement
const searchForm = document.querySelector('.search-form');
const searchInput = searchForm?.querySelector('input[name="q"]');

if (searchInput) {
    // Focus on search input when pressing Ctrl+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
    });
    
    // Clear search on escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
        }
    });
}
</script>