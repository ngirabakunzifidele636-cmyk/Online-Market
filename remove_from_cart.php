<?php
include 'config.php';

if (isset($_GET['index'])) {
    $index = $_GET['index'];
    if (isset($_SESSION['cart'][$index])) {
        $product_name = $_SESSION['cart'][$index]['name'];
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $_SESSION['success_message'] = "$product_name removed from cart!";
    }
}

header('Location: cart.php');
exit;


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to remove items from cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? null;
    
    if (!$item_id) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        exit();
    }
    
    try {
        $pdo = getDatabaseConnection();
        
        // Remove item from cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>