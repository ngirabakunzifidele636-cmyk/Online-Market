<?php
session_start();

// Temporary hardcoded credentials
$host = 'localhost';
$dbname = 'online_market';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Rest of your process_order.php code...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // $pdo = getDatabaseConnection();
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Get cart items
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.stock_quantity, (c.quantity * c.price) as item_total 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if cart is empty
        if (empty($cart_items)) {
            throw new Exception('Your cart is empty');
        }
        
        // Check stock availability
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                throw new Exception("Not enough stock for {$item['name']}. Available: {$item['stock_quantity']}");
            }
        }
        
        // Calculate totals
        $subtotal = 0;
        $total_items = 0;
        foreach ($cart_items as $item) {
            $subtotal += $item['item_total'];
            $total_items += $item['quantity'];
        }
        $tax = $subtotal * 0.08;
        $shipping = $subtotal > 50 ? 0 : 5.99;
        $total = $subtotal + $tax + $shipping;
        
        // Generate unique order number
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        // Get form data
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $country = $_POST['country'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $payment_method = $_POST['payment_method'] ?? 'card';
        $customer_notes = $_POST['customer_notes'] ?? '';
        
        // Create shipping address string
        $shipping_address = "{$first_name} {$last_name}\n{$address}\n{$city}, {$country} {$postal_code}\nPhone: {$phone}";
        // Send order confirmation email
require_once 'email_config.php';

// Get user details for email
$user_stmt = $pdo->prepare("SELECT username, email, first_name FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get order items for email
$items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Send email
$email_subject = "Order Confirmation - #{$order_number}";
$email_message = EmailTemplates::orderConfirmation($order, $order_items, $user);
EmailConfig::sendEmail($user['email'], $email_subject, $email_message);

// Store email sent info
$_SESSION['email_sent'] = true;
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                order_number, user_id, total_amount, subtotal, tax_amount, shipping_amount,
                payment_status, order_status, shipping_address, billing_address, customer_notes,
                payment_method, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $order_number,
            $_SESSION['user_id'],
            $total,
            $subtotal,
            $tax,
            $shipping,
            $shipping_address,
            $shipping_address, // Using same as billing for now
            $customer_notes,
            $payment_method
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Insert order items and update product stock
        foreach ($cart_items as $item) {
            // Insert order item
            $stmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, product_price, quantity, total_price
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
                $item['item_total']
            ]);
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Clear user's cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Commit transaction
        $pdo->commit();
        
        // Store order success in session
        $_SESSION['order_success'] = true;
        $_SESSION['order_number'] = $order_number;
        $_SESSION['order_total'] = $total;
        
        // Redirect to order confirmation page
        header('Location: order_confirmation.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $_SESSION['order_error'] = $e->getMessage();
        header('Location: checkout.php');
        exit();
    }
} else {
    header('Location: cart.php');
    exit();
}
?>