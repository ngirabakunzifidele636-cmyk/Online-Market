<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Creating Sample Orders</h1>";
    
    // Get user ID
    $user_id = $_SESSION['user_id'];
    
    // Get some products to create orders from
    $products_stmt = $pdo->query("SELECT id, name, price FROM products LIMIT 3");
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p style='color: red;'>No products found. Please add products first.</p>";
        echo "<p><a href='add_sample_products.php'>Add Sample Products</a></p>";
        exit();
    }
    
    // Create sample orders
    $sample_orders = [
        [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'items' => [
                ['product_id' => $products[0]['id'], 'quantity' => 2, 'name' => $products[0]['name'], 'price' => $products[0]['price']],
                ['product_id' => $products[1]['id'], 'quantity' => 1, 'name' => $products[1]['name'], 'price' => $products[1]['price']]
            ]
        ],
        [
            'status' => 'shipped',
            'payment_status' => 'paid', 
            'items' => [
                ['product_id' => $products[2]['id'], 'quantity' => 1, 'name' => $products[2]['name'], 'price' => $products[2]['price']]
            ]
        ],
        [
            'status' => 'processing',
            'payment_status' => 'paid',
            'items' => [
                ['product_id' => $products[0]['id'], 'quantity' => 1, 'name' => $products[0]['name'], 'price' => $products[0]['price']],
                ['product_id' => $products[1]['id'], 'quantity' => 1, 'name' => $products[1]['name'], 'price' => $products[1]['price']],
                ['product_id' => $products[2]['id'], 'quantity' => 1, 'name' => $products[2]['name'], 'price' => $products[2]['price']]
            ]
        ]
    ];
    
    $orders_created = 0;
    
    foreach ($sample_orders as $sample_order) {
        // Calculate totals
        $subtotal = 0;
        foreach ($sample_order['items'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $tax = $subtotal * 0.08;
        $shipping = $subtotal > 50 ? 0 : 5.99;
        $total = $subtotal + $tax + $shipping;
        
        // Generate order number
        $order_number = 'SAMPLE-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        // Insert order
        $order_stmt = $pdo->prepare("
            INSERT INTO orders (
                order_number, user_id, total_amount, subtotal, tax_amount, shipping_amount,
                payment_status, order_status, shipping_address, billing_address, 
                payment_method, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'card', DATE_SUB(NOW(), INTERVAL ? DAY))
        ");
        
        // Create different dates for orders (recent to older)
        $days_ago = $orders_created * 7; // 0 days, 7 days, 14 days ago
        
        $shipping_address = "John Doe\n123 Main Street\nNew York, NY 10001\nPhone: (555) 123-4567";
        
        $order_stmt->execute([
            $order_number,
            $user_id,
            $total,
            $subtotal,
            $tax,
            $shipping,
            $sample_order['payment_status'],
            $sample_order['status'],
            $shipping_address,
            $shipping_address,
            $days_ago
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Insert order items
        foreach ($sample_order['items'] as $item) {
            $item_total = $item['price'] * $item['quantity'];
            
            $item_stmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, product_price, quantity, total_price
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $item_stmt->execute([
                $order_id,
                $item['product_id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
                $item_total
            ]);
        }
        
        $orders_created++;
        echo "<p style='color: green;'>✅ Created order #$order_number with status: {$sample_order['status']}</p>";
    }
    
    echo "<h2 style='color: green;'>🎉 Successfully created $orders_created sample orders!</h2>";
    echo "<p><a href='dashboard.php'>View Your Dashboard</a></p>";
    echo "<p><a href='debug_orders.php'>Debug Orders</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>