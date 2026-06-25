<?php
// Prevent multiple includes
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// File upload configuration
define('UPLOAD_DIR', 'uploads/profile_images/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Database configuration for XAMPP
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'online_market');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', ''); 

// Initialize cart 
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ensure upload directory exists
$upload_dir = 'uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    // Create .htaccess for security
    file_put_contents($upload_dir . '.htaccess', "Order deny,allow\nDeny from all");
}

// Initialize messages
if (!isset($_SESSION['success_message'])) {
    $_SESSION['success_message'] = '';
}

if (!isset($_SESSION['error_message'])) {
    $_SESSION['error_message'] = '';
}

// upload directory 
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// REMOVED THE PROBLEMATIC CODE BLOCK THAT WAS CAUSING LINE 60 ERROR

$upload_dir = 'uploads/profile_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    // Create .htaccess for security
    file_put_contents($upload_dir . '.htaccess', "Order deny,allow\nDeny from all");
}

// Database connection function - check if it already exists
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch(PDOException $e) {
            die("Database connection failed. Please make sure:
            <br>1. MySQL is running in XAMPP
            <br>2. Database 'online_market' exists
            <br>3. You're using the correct XAMPP credentials
            <br>Error: " . $e->getMessage());
        }
    }
}

// Profile image upload function
if (!function_exists('handleProfileImageUpload')) {
    function handleProfileImageUpload($file, $user_id) {
        $result = ['success' => false, 'error' => '', 'file_path' => ''];
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            $result['error'] = "File is too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
            return $result;
        }
        
        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ALLOWED_TYPES)) {
            $result['error'] = "Only JPG, JPEG, PNG, and GIF files are allowed";
            return $result;
        }
        
        // Verify image
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) {
            $result['error'] = "File is not a valid image";
            return $result;
        }
        
        // Generate unique filename
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $file_path = UPLOAD_DIR . $filename;
        
        // Ensure upload directory exists
        if (!file_exists(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $result['success'] = true;
            $result['file_path'] = $file_path;
        } else {
            $result['error'] = "Failed to upload file. Check directory permissions.";
        }
        
        return $result;
    }
}

// Function to get upload error message
if (!function_exists('getUploadError')) {
    function getUploadError($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return "File exceeds upload_max_filesize directive in php.ini";
            case UPLOAD_ERR_FORM_SIZE:
                return "File exceeds MAX_FILE_SIZE directive in HTML form";
            case UPLOAD_ERR_PARTIAL:
                return "File was only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "File upload stopped by extension";
            default:
                return "Unknown upload error";
        }
    }
}

// ==================== NOTIFICATION FUNCTIONS ====================

// Function to format time ago
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

// Function to get user notifications
if (!function_exists('getUserNotifications')) {
    function getUserNotifications($user_id, $limit = 10) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
}

// Function to get unread notification count
if (!function_exists('getUnreadNotificationCount')) {
    function getUnreadNotificationCount($user_id) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
}

// Function to mark notification as read
if (!function_exists('markNotificationAsRead')) {
    function markNotificationAsRead($notification_id, $user_id) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
}

// Function to mark all notifications as read
if (!function_exists('markAllNotificationsAsRead')) {
    function markAllNotificationsAsRead($user_id) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
}

// Function to add cart notification
if (!function_exists('addCartNotification')) {
    function addCartNotification($user_id, $product_name, $product_price) {
        global $conn;
        try {
            $title = "Added to Cart";
            $message = "{$product_name} has been added to your cart.";
            $type = "success";
            $icon = "fa-cart-plus";
            $category = "cart";
            $action_url = "cart.php";
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, is_read)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            return $stmt->execute([
                $user_id, 
                $title, 
                $message, 
                $type, 
                $icon, 
                $category, 
                $action_url
            ]);
        } catch (PDOException $e) {
            error_log("Error adding cart notification: " . $e->getMessage());
            return false;
        }
    }
}

// Function to delete notification
if (!function_exists('deleteNotification')) {
    function deleteNotification($notification_id, $user_id) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
}

// Function to clear all notifications
if (!function_exists('clearAllNotifications')) {
    function clearAllNotifications($user_id) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                DELETE FROM notifications 
                WHERE user_id = ?
            ");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Error clearing all notifications: " . $e->getMessage());
            return false;
        }
    }
}

// Function to add system notification
if (!function_exists('addSystemNotification')) {
    function addSystemNotification($user_id, $title, $message, $type = 'info', $category = 'system', $action_url = '') {
        global $conn;
        try {
            $icons = [
                'success' => 'fa-check-circle',
                'warning' => 'fa-exclamation-triangle',
                'info' => 'fa-info-circle',
                'error' => 'fa-times-circle',
                'cart' => 'fa-cart-plus',
                'order' => 'fa-shipping-fast',
                'offer' => 'fa-tags'
            ];
            
            $icon = $icons[$type] ?? 'fa-bell';
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, is_read)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            return $stmt->execute([
                $user_id, 
                $title, 
                $message, 
                $type, 
                $icon, 
                $category, 
                $action_url
            ]);
        } catch (PDOException $e) {
            error_log("Error adding system notification: " . $e->getMessage());
            return false;
        }
    }
}

// Function to get notifications by category
if (!function_exists('getNotificationsByCategory')) {
    function getNotificationsByCategory($user_id, $category, $limit = 20) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND category = ?
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$user_id, $category, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting notifications by category: " . $e->getMessage());
            return [];
        }
    }
}

// Function to get recent notifications (last 24 hours)
if (!function_exists('getRecentNotifications')) {
    function getRecentNotifications($user_id, $hours = 24) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user_id, $hours]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent notifications: " . $e->getMessage());
            return [];
        }
    }
}

// Create global database connection
$conn = getDBConnection();

// Auto-create notifications table if it doesn't exist
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'")->fetch();
    if (!$table_check) {
        $sql = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'info',
            icon VARCHAR(100) NOT NULL DEFAULT 'fa-bell',
            category VARCHAR(50) NOT NULL DEFAULT 'system',
            is_read BOOLEAN NOT NULL DEFAULT FALSE,
            action_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        
        // Create foreign key if users table exists
        $users_table = $conn->query("SHOW TABLES LIKE 'users'")->fetch();
        if ($users_table) {
            try {
                $conn->exec("ALTER TABLE notifications ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
            } catch (Exception $e) {
                // Foreign key might already exist or fail - that's OK
            }
        }
        
        error_log("Notifications table created successfully");
    }
} catch (PDOException $e) {
    error_log("Error creating notifications table: " . $e->getMessage());
}
?>