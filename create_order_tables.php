<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Creating Order Tables</h1>";
    
    // Create orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            tax_amount DECIMAL(10,2) DEFAULT 0,
            shipping_amount DECIMAL(10,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT,
            billing_address TEXT,
            customer_notes TEXT,
            admin_notes TEXT,
            payment_method VARCHAR(50),
            transaction_id VARCHAR(100),
            shipping_method VARCHAR(100),
            tracking_number VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Created orders table</p>";
    
    // Create order_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            product_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            attributes JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");
    echo "<p>✅ Created order_items table</p>";
    
    // Create payments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_gateway VARCHAR(50),
            transaction_id VARCHAR(100),
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'USD',
            status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            gateway_response JSON,
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Created payments table</p>";
    
    // Create indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_number ON orders(order_number)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_payments_order ON payments(order_id)");
    
    echo "<p>✅ Created all indexes</p>";
    
    echo "<h2 style='color: green;'>🎉 Order tables created successfully!</h2>";
    echo "<p><a href='checkout.php'>Go to Checkout</a> | <a href='products.php'>Continue Shopping</a></p>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>