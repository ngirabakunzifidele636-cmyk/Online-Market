<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index = $_POST['index'];
    $quantity = intval($_POST['quantity']);
    
    if (isset($_SESSION['cart'][$index]) && $quantity > 0 && $quantity <= 10) {
        $_SESSION['cart'][$index]['quantity'] = $quantity;
        $_SESSION['success_message'] = "Cart updated successfully!";
    }
    
    header('Location: cart.php');
    exit;
}


// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $item_id = $_POST['item_id'] ?? null;
//     $quantity = $_POST['quantity'] ?? 1;
    
//     if (!$item_id || $quantity < 1) {
//         echo json_encode(['success' => false, 'message' => 'Invalid request']);
//         exit();
//     }
    
//     try {
//         $pdo = getDatabaseConnection();
        
//         // Check if item belongs to user and get product stock
//         $stmt = $pdo->prepare("
//             SELECT c.*, p.stock_quantity 
//             FROM cart c 
//             JOIN products p ON c.product_id = p.id 
//             WHERE c.id = ? AND c.user_id = ?
//         ");
//         $stmt->execute([$item_id, $_SESSION['user_id']]);
//         $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
//         if (!$cart_item) {
//             echo json_encode(['success' => false, 'message' => 'Cart item not found']);
//             exit();
//         }
        
//         // Check stock availability
//         if ($quantity > $cart_item['stock_quantity']) {
//             echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
//             exit();
//         }
        
//         // Update quantity
//         $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
//         $stmt->execute([$quantity, $item_id, $_SESSION['user_id']]);
        
//         echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
        
//     } catch(PDOException $e) {
//         echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
//     }
// } else {
//     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
// }
//
 ?>