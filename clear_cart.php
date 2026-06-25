<?php
include 'config.php';

$_SESSION['cart'] = [];
$_SESSION['success_message'] = "Cart cleared successfully!";

header('Location: cart.php');
exit;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to clear cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDatabaseConnection();
        
        // Clear all items from user's cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Cart cleared successfully']);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>