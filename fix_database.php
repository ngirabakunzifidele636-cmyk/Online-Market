<?php
// fix_database.php
session_start();
require_once 'config.php';

echo "<h2>Fixing Database Structure...</h2>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; }</style>";

try {
    // Users table fixes
    $users_fixes = [
        'reset_token' => "ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL AFTER password",
        'reset_token_expires' => "ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL AFTER reset_token",
        'state' => "ALTER TABLE users ADD COLUMN state VARCHAR(100) NULL AFTER address",
        'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER address", 
        'zip_code' => "ALTER TABLE users ADD COLUMN zip_code VARCHAR(20) NULL AFTER city",
        'is_admin' => "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER email_verified",
        'is_active' => "ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER is_admin"
    ];
    
    foreach ($users_fixes as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM users LIKE '$column'")->fetch();
        if (!$check) {
            $conn->exec($sql);
            echo "<p style='color: green;'>✓ Added '$column' to users table</p>";
        } else {
            echo "<p style='color: blue;'>✓ '$column' already exists</p>";
        }
    }

    // Orders table fixes
    $orders_fixes = [
        'payment_transaction_id' => "ALTER TABLE orders ADD COLUMN payment_transaction_id VARCHAR(255) NULL AFTER payment_status",
        'subtotal' => "ALTER TABLE orders ADD COLUMN subtotal DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total_amount",
        'tax_amount' => "ALTER TABLE orders ADD COLUMN tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER subtotal",
        'shipping_amount' => "ALTER TABLE orders ADD COLUMN shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER tax_amount"
    ];
    
    foreach ($orders_fixes as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM orders LIKE '$column'")->fetch();
        if (!$check) {
            $conn->exec($sql);
            echo "<p style='color: green;'>✓ Added '$column' to orders table</p>";
        } else {
            echo "<p style='color: blue;'>✓ '$column' already exists</p>";
        }
    }

    // Order_items table fixes
    $order_items_fixes = [
        'product_image' => "ALTER TABLE order_items ADD COLUMN product_image VARCHAR(500) NOT NULL AFTER product_name"
    ];
    
    foreach ($order_items_fixes as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM order_items LIKE '$column'")->fetch();
        if (!$check) {
            $conn->exec($sql);
            echo "<p style='color: green;'>✓ Added '$column' to order_items table</p>";
        } else {
            echo "<p style='color: blue;'>✓ '$column' already exists</p>";
        }
    }

    echo "<h3 style='color: green;'>🎉 Database fixed successfully!</h3>";
    echo "<p><a href='checkout.php' style='color: blue;'>Go to Checkout</a> | <a href='index.php' style='color: blue;'>Go to Home</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>