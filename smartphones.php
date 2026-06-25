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

// Function to add cart notification
function addCartNotification($user_id, $product_name, $product_price) {
    // This function would typically add notification to database
    // For now, we'll just return true as a placeholder
    return true;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $product_name = htmlspecialchars(trim($_POST['product_name']));
    $product_price = filter_var($_POST['product_price'], FILTER_VALIDATE_FLOAT);
    $product_image = filter_var($_POST['product_image'], FILTER_VALIDATE_URL);
    $quantity = 1;
    
    // Validate input
    if (!$product_id || !$product_price || !$product_image) {
        $error = "Invalid product data!";
    } else {
        try {
            // Define cart table SQL
            $cart_sql = "CREATE TABLE IF NOT EXISTS cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                product_price DECIMAL(10, 2) NOT NULL,
                product_image VARCHAR(500) NOT NULL,
                quantity INT DEFAULT 1,
                total_price DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            
            // Check if cart table exists
            $check_table = $conn->query("SHOW TABLES LIKE 'cart'");
            if ($check_table->rowCount() == 0) {
                // Create cart table if it doesn't exist
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
            
            // Add notification
            addCartNotification($user_id, $product_name, $product_price);
            
            $_SESSION['success_message'] = "✅ " . $product_name . " added to cart!";
            header("Location: smartphones.php");
            exit();
            
        } catch (PDOException $e) {
            $error = "Error adding to cart: " . $e->getMessage();
        }
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
    <title>Smart Phones - TechStore</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 10px;
            color: white;
            margin-bottom: 30px;
        }
        .feature-box {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <?php include 'header.php'; ?>
          
    <div class="container mt-4">
    </nav>
        <div class="category-banner">
            <h1><i class="fas fa-mobile-alt"></i> Smart Phones</h1>
            <p class="lead">Discover the latest smartphones with cutting-edge technology and features.</p>
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
            // Smartphones data with more products
            $products = [
                [
                    'id' => 101, 
                    'name' => 'iPhone 16 Pro', 
                    'price' => 1399.99, 
                    'image' => 'https://tse4.mm.bing.net/th/id/OIP.79iLKQ4BbAkA61zg9ng89QHaEK?pid=Api&P=0&h=220',
                    'description' => 'A17 Pro chip, 6.7" Super Retina XDR'
                ],
                [
                    'id' => 102, 
                    'name' => 'iPhone 17', 
                    'price' => 1799.99, 
                    'image' => 'https://tse1.mm.bing.net/th/id/OIP.RyXsQQsUUE7OQwceYrkWWQHaEK?pid=Api&P=0&h=220',
                    'description' => 'Dynamic Island, 48MP Camera, Titanium design'
                ],
                [
                    'id' => 103, 
                    'name' => 'Samsung Galaxy S24 Ultra', 
                    'price' => 1499.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.UpAy4ZW2u3AEzD0jAJ25hgHaE8?pid=Api&P=0&h=220',
                    'description' => '200MP Camera, Snapdragon 8 Gen 3'
                ],
                [
                    'id' => 104, 
                    'name' => 'Samsung Galaxy S25 Ultra', 
                    'price' => 1599.99, 
                    'image' => 'https://images.unsplash.com/photo-1738830251513-a7bfef4b53c6?ixlib=rb-4.1.0&auto=format&fit=crop&w=500&q=60',
                    'description' => 'Hole-Punch Cutout, 200MP, S Pen included'
                ],
                [
                    'id' => 105, 
                    'name' => 'Google Pixel 8 Pro', 
                    'price' => 999.99, 
                    'image' => 'https://tse3.mm.bing.net/th/id/OIP.U7HVeS3kX0GfJonXrsfZAQHaEK?pid=Api&P=0&h=220',
                    'description' => 'Tensor G3 chip, 6.7" OLED, 50MP camera'
                ],
                [
                    'id' => 106, 
                    'name' => 'OnePlus 12', 
                    'price' => 899.99, 
                    'image' => 'https://tse4.mm.bing.net/th/id/OIP.v9W8W4_2F11-B1m8KahemwHaEC?pid=Api&P=0&h=220',
                    'description' => 'Snapdragon 8 Gen 3, 100W fast charging'
                ],
                [
                    'id' => 107, 
                    'name' => 'Xiaomi 14 Pro', 
                    'price' => 849.99, 
                    'image' => 'https://tse3.mm.bing.net/th/id/OIP.k9BZyL4Gh3BPvbEWDE8hNAHaEK?pid=Api&P=0&h=220',
                    'description' => 'Leica camera, 6.73" AMOLED, 120Hz'
                ],
                [
                    'id' => 108, 
                    'name' => 'Sony Xperia 1 V', 
                    'price' => 1299.99, 
                    'image' => 'https://tse3.mm.bing.net/th/id/OIP.RKnr6UKk_7wmvWZJ1aEIUQHaEi?pid=Api&P=0&h=220',
                    'description' => '4K OLED, Zeiss optics, CinemaWide display'
                ],
                [
                    'id' => 109, 
                    'name' => 'Nothing Phone 2', 
                    'price' => 699.99, 
                    'image' => 'https://tse1.mm.bing.net/th/id/OIP.k9z66uYxQAJrDlcHWsgccgHaEK?pid=Api&P=0&h=220',
                    'description' => 'Glyph Interface, Snapdragon 8+ Gen 1'
                ],
                [
                    'id' => 110, 
                    'name' => 'Asus ROG Phone 8', 
                    'price' => 1199.99, 
                    'image' => 'https://tse3.mm.bing.net/th/id/OIP.EuEKwQSSKOtR78gLQl-KYwHaEK?pid=Api&P=0&h=220',
                    'description' => 'Gaming phone, 165Hz AMOLED, AirTriggers'
                ],
                [
                    'id' => 111, 
                    'name' => 'Motorola Edge 40 Pro', 
                    'price' => 799.99, 
                    'image' => 'https://tse1.mm.bing.net/th/id/OIP.z92KRJmGv43_Ll6iuHLhrQHaEK?pid=Api&P=0&h=220',
                    'description' => 'Curved pOLED, 165Hz, 125W turbo charging'
                ],
                [
                    'id' => 112, 
                    'name' => 'iPhone 15 Pro Max', 
                    'price' => 1199.99, 
                    'image' => 'https://tse4.mm.bing.net/th/id/OIP.HdFzMBPOKKKvmqjw28b1NAHaE7?pid=Api&P=0&h=220',
                    'description' => 'Titanium, A16 Bionic, Action button'
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