<?php
session_start();
include 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['is_admin'] != 1) {
    $_SESSION['error_message'] = "Access denied. Admin privileges required.";
    header('Location: index.php');
    exit();
}

// Get order ID from URL
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    $_SESSION['error_message'] = "No order specified.";
    header('Location: dashboard.php');
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Delete order items first (due to foreign key constraints)
    $delete_items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $delete_items_stmt->execute([$order_id]);
    
    // Delete the order
    $delete_order_stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $delete_order_stmt->execute([$order_id]);
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Order deleted successfully!";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    $_SESSION['error_message'] = "Error deleting order: " . $e->getMessage();
}

header('Location: dashboard.php');
exit();
?>