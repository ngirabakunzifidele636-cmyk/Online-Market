<?php
// admin/send_notification.php
session_start();
require_once '../config.php';

// Check if user is admin (you need to implement this)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $title = $_POST['title'];
    $message = $_POST['message'];
    $type = $_POST['type'];
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, icon, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $title, $message, $type, 'fa-bell']);
        
        $_SESSION['success'] = "Notification sent successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to send notification: " . $e->getMessage();
    }
    
    header('Location: notifications.php');
    exit();
}
?>