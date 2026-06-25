<?php 
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $quantity = 1;
    
    try {
        // Check if cart table exists
        $cart_table = $conn->query("SHOW TABLES LIKE 'cart'")->fetch();
        
        if (!$cart_table) {
            
            $cart_sql = "
            CREATE TABLE IF NOT EXISTS cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                product_price DECIMAL(10,2) NOT NULL,
                product_image VARCHAR(500) NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                total_price DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $conn->exec($cart_sql);
        }
        
        // Check if item already in cart
        $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $check_stmt->execute([$user_id, $product_id]);
        $existing_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_price = $product_price * $quantity;
        
        if ($existing_item) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            $new_total = $product_price * $new_quantity;
            
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, total_price = ? WHERE id = ?");
            $update_stmt->execute([$new_quantity, $new_total, $existing_item['id']]);
        } else {
            // Add new item
            $insert_stmt = $conn->prepare("
                INSERT INTO cart (user_id, product_id, product_name, product_price, product_image, quantity, total_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->execute([
                $user_id, 
                $product_id, 
                $product_name, 
                $product_price, 
                $product_image,
                $quantity, 
                $total_price
            ]);
        }
        
        $_SESSION['success_message'] = "✅ " . $product_name . " added to cart!";
        header("Location: headphones.php");
        exit();
        
    if (isset($_SESSION['user_id'])) {
            addCartNotification($_SESSION['user_id'], $product_name, $product_price);
        }
        
        $_SESSION['success_message'] = "✅ " . $product_name . " added to cart!";
        header("Location: " . basename($_SERVER['PHP_SELF']));
        exit();
        
    } catch (PDOException $e) {
        $error = "Error adding to cart: " . $e->getMessage();
    }
}
// Display success message if exists
if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    $_SESSION['success_message'] = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Headphones - TechStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .price {
            color: #28a745;
            font-weight: bold;
            font-size: 1.2em;
        }
        .btn-add-to-cart {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-add-to-cart:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: scale(1.05);
        }
        .category-banner {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            padding: 30px;
            border-radius: 10px;
            color: white;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
     <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <div class="category-banner">
            <h1><i class="fas fa-headphones"></i> Headphones</h1>
            <p class="lead">Immerse yourself in premium audio with our collection of headphones and earbuds.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= $success ?>
                <a href="cart.php" class="btn btn-outline-success btn-sm ms-2">View Cart</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <?php
            // Headphones data
            $products = [
                [
                    'id' => 401, 
                    'name' => 'Sony WH-1000XM5', 
                    'price' => 399.99, 
                    'image' => 'https://tse4.mm.bing.net/th/id/OIP.pXQ8_gwu1GEdd5z_M8taMAHaEK?pid=Api&P=0&h=220',
                    'description' => 'Industry-leading noise cancellation, 30-hour battery'
                ],
                [
                    'id' => 402, 
                    'name' => 'Apple AirPods Max', 
                    'price' => 549.99, 
                    'image' => 'https://tse4.mm.bing.net/th/id/OIP.zFnHhF4VdaqfvpKvYn6mdAHaEm?pid=Api&P=0&h=220',
                    'description' => 'Active Noise Cancellation, Spatial Audio'
                ],
                [
                    'id' => 403, 
                    'name' => 'Bose QuietComfort Ultra', 
                    'price' => 429.99, 
                    'image' => 'https://tse4.mm.bing.net/th/id/OIP.JJt8_-0URT9LYc6UJFzcGAHaFj?pid=Api&P=0&h=220',
                    'description' => 'Immersive Audio, 24-hour battery life'
                ],
                [
                    'id' => 404, 
                    'name' => 'Sennheiser Momentum 4', 
                    'price' => 349.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP._ACONSOHl05R5Hmcvp_4YwHaHa?pid=Api&P=0&h=220',
                    'description' => '60-hour battery, Adaptive Noise Cancellation'
                ],
                [
                    'id' => 405, 
                    'name' => 'AirPods Pro 2', 
                    'price' => 249.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.I7bCrW_97R9jbay0sAldKwHaEK?pid=Api&P=0&h=220',
                    'description' => 'Active Noise Cancellation, MagSafe charging'
                ],
                [
                    'id' => 406, 
                    'name' => 'Beats Studio Pro', 
                    'price' => 349.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.ryNkzXQ-c7COmi4Vcnnv0QHaEK?pid=Api&P=0&h=220',
                    'description' => 'Apple H1 chip, 40-hour battery, ANC'
                ],
                [
                    'id' => 407, 
                    'name' => 'Jabra Elite 10', 
                    'price' => 249.99, 
                    'image' => 'https://tse4.mm.bing.net/th/id/OIP._JRnChXZ-_8dLleMGK5f4wHaEk?pid=Api&P=0&h=220',
                    'description' => 'Dolby Atmos, adaptive ANC, 8-hour battery'
                ],
                [
                    'id' => 408, 
                    'name' => 'Samsung Galaxy Buds2 Pro', 
                    'price' => 229.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.16syOSSl1PljXD1r3nOBcwHaHr?pid=Api&P=0&h=220',
                    'description' => '24-bit Hi-Fi, Intelligent ANC, 360 Audio'
                ],
                [
                    'id' => 409, 
                    'name' => 'Bowers & Wilkins Px8', 
                    'price' => 699.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.MvsRiHLYGn2-AR9InTCUjgHaEc?pid=Api&P=0&h=220',
                    'description' => 'Carbon cone drivers, 30-hour battery, ANC'
                ],
                [
                    'id' => 410, 
                    'name' => 'Audio-Technica ATH-M50xBT2', 
                    'price' => 199.99, 
                    'image' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=70',
                    'description' => 'Studio monitor sound, 50-hour battery'
                ],
                [
                    'id' => 411, 
                    'name' => 'Sony WF-1000XM5', 
                    'price' => 299.99, 
                    'image' => 'https://tse1.mm.bing.net/th/id/OIP.Kb-u4ogPLzDMGS_sbaMq8QHaHa?pid=Api&P=0&h=220',
                    'description' => 'Industry-leading ANC earbuds, 24-hour battery'
                ],
                [
                    'id' => 412, 
                    'name' => 'Bang & Olufsen Beoplay H95', 
                    'price' => 899.99, 
                    'image' => 'https://tse1.mm.bing.net/th/id/OIP.2vBGsyS89aDUeu0c7Gt1GAHaHH?pid=Api&P=0&h=220',
                    'description' => 'Luxury headphones, 50-hour battery, ANC'
                ],
            ];

            foreach ($products as $product) {
                echo '
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <img src="' . $product['image'] . '" class="card-img-top product-image" alt="' . $product['name'] . '">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">' . $product['name'] . '</h5>
                            <p class="card-text flex-grow-1">' . $product['description'] . '</p>
                            <div class="mt-auto">
                                <p class="price">$' . number_format($product['price'], 2) . '</p>
                                <form method="POST" action="smartphones.php">
                                    <input type="hidden" name="product_id" value="' . $product['id'] . '">
                                    <input type="hidden" name="product_name" value="' . htmlspecialchars($product['name']) . '">
                                    <input type="hidden" name="product_price" value="' . $product['price'] . '">
                                    <input type="hidden" name="product_image" value="' . htmlspecialchars($product['image']) . '">
                                    <button type="submit" name="add_to_cart" class="btn btn-add-to-cart w-100">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container mt-5">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="feature-box p-4">
                    <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                    <h4>Free Shipping</h4>
                    <p class="text-muted">Free shipping on all phone orders</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box p-4">
                    <i class="fas fa-sync-alt fa-3x text-success mb-3"></i>
                    <h4>30-Day Return</h4>
                    <p class="text-muted">30-day return policy on smartphones</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box p-4">
                    <i class="fas fa-shield-alt fa-3x text-info mb-3"></i>
                    <h4>2-Year Warranty</h4>
                    <p class="text-muted">Extended warranty on all phones</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>TechStore</h5>
                    <p>Your trusted partner for all tech needs since 2025.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; 2025 TechStore. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>


<script>
// Add cart notification functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all "Add to Cart" buttons
    document.querySelectorAll('button[name="add_to_cart"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            // Get product details from the form
            const form = this.closest('form');
            const productName = form.querySelector('input[name="product_name"]').value;
            const productPrice = form.querySelector('input[name="product_price"]').value;
            
            // Store cart addition in session storage
            sessionStorage.setItem('lastCartAddition', JSON.stringify({
                name: productName,
                price: productPrice,
                time: new Date().toISOString()
            }));
            
            // Show immediate feedback
            showCartNotification(productName);
        });
    });
});

function showCartNotification(productName) {
    // Create and show a temporary notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-success position-fixed top-0 end-0 m-3';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <i class="fas fa-check-circle"></i> <strong>${productName}</strong> added to cart!
        <a href="cart.php" class="btn btn-sm btn-outline-success ms-2">View Cart</a>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}
</script>