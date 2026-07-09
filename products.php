<?php 
session_start();
include 'config.php';



if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';




if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $quantity = 1; 
    
    
    try {
        
        
        $cart_table = $conn->query("SHOW TABLES LIKE 'cart'")->fetch();
        
        if (!$cart_table) {
            // Create cart table 
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
        
        
        
        $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $check_stmt->execute([$user_id, $product_id]);
        $existing_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_price = $product_price * $quantity;
        
        if ($existing_item) {
            
            
            $new_quantity = $existing_item['quantity'] + $quantity;
            $new_total = $product_price * $new_quantity;
            
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, total_price = ? WHERE id = ?");
            $update_stmt->execute([$new_quantity, $new_total, $existing_item['id']]);
        } else {
            
            
           
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
        header("Location: products.php");
        exit();
        
    } catch (PDOException $e) {
        $error = "Error adding to cart: " . $e->getMessage();
    }
}


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
    <title>Products - TechStore</title>
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
    </style>
</head>
<body>
    
    

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <?php include 'header.php'; ?>

    <div class="container mt-4">
           
    
    

    <div class="container mt-4">
        <h1 class="mb-4">Our Products <i class="fas fa-shopping-bag"></i></h1>
        <p class="lead">Discover our amazing collection of tech products.</p>

        <?php if ($success): ?>
            <!-- <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= $success ?>
                <a href="cart.php" class="btn btn-outline-success btn-sm ms-2">View Cart</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div> -->
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

                <div class="row mt-4">
            <?php
            
            $products = [
                [
                    'id' => 1, 
                    'name' => 'MacBook Pro 16"', 
                    'price' => 2399.99, 
                    'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                    'description' => 'M2 Pro chip, 32GB RAM, 1TB SSD'
                ],
                [
                    'id' => 2, 
                    'name' => 'iPhone 14 Pro', 
                    'price' => 999.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.9hc21uhoFsvmooUD4cy-hQHaEk?pid=Api&P=0&h=220',
                    'description' => 'Dynamic Island, 48MP Camera'
                ],
                [
                    'id' => 3, 
                    'name' => 'Sony WH-1000XM5', 
                    'price' => 299.99, 
                    'image' => 'https://tse3.mm.bing.net/th/id/OIP.pq794ka6QwngkQCDQsIIswHaEk?pid=Api&P=0&h=220',
                    'description' => 'Industry-leading noise cancellation'
                ],
                [
                    'id' => 4, 
                    'name' => 'Apple Watch Series 8', 
                    'price' => 399.99, 
                    'image' => 'https://tse4.mm.bing.net/th/id/OIP.gg4NML3X7ZHCD93mhoLBUAHaEK?pid=Api&h=220&P=0',
                    'description' => 'Advanced health monitoring'
                ],
                [
                    'id' => 5, 
                    'name' => 'iPhone 16 Pro', 
                    'price' => 1399.99, 
                    'image' => 'https://tse1.mm.bing.net/th/id/OIP.4kwxCwKQDvW_CerEY6UZwgHaEK?pid=Api&P=0&h=220',
                    'description' => 'Dynamic Island, 48MP Camera'
                ],
                [
                    'id' => 6, 
                    'name' => 'iPhone 17', 
                    'price' => 1799.99, 
                    'image' => 'https://images.unsplash.com/photo-1757709608566-4b9fd41a7af5?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MjB8fGlwaG9uZSUyMDE3fGVufDB8fDB8fHww&auto=format&fit=crop&q=60&w=500',
                    'description' => 'Dynamic Island, 48MP Camera'
                ],
                [
                    'id' => 7, 
                    'name' => 'Samsung galaxy S24 ultra', 
                    'price' => 1499.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.UpAy4ZW2u3AEzD0jAJ25hgHaE8?pid=Api&P=0&h=220',
                    'description' => 'Hole-Punch Cutout, 200MP'
                ],
                [
                    'id' => 8, 
                    'name' => 'Samsung galaxy S25 ultra', 
                    'price' => 1599.99, 
                    'image' => 'https://images.unsplash.com/photo-1738830251513-a7bfef4b53c6?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8U2Ftc3VuZyUyMGdhbGF4eSUyMFMyNSUyMHVsdHJhfGVufDB8fDB8fHww&auto=format&fit=crop&q=60&w=500',
                    'description' => 'Hole-Punch Cutout, 200MP'
                ],
                [
                    'id' => 9, 
                    'name' => 'HP Intel Core i5', 
                    'price' => 1099.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.3SFcCP8zFI65iEFcP945qAHaHa?pid=Api&h=220&P=0',
                    'description' => '8GB RAM, 256GB SSD, Windows 10 Home'
                ],
                [
                    'id' => 10, 
                    'name' => 'Dell laptop', 
                    'price' => 999.99, 
                    'image' => 'https://www.digitaltrends.com/wp-content/uploads/2018/02/dell-xps-13-screen-lid1.jpg?fit=1500%2C1000&p=1',
                    'description' => '16GB RAM, 1TB SSD, Windows 11 Home'
                ],
                [
                    'id' => 11, 
                    'name' => 'Galaxy Books 5Pro 360 ', 
                    'price' => 1999.99, 
                    'image' => 'https://tse2.mm.bing.net/th/id/OIP.sxhTC2pfQvfyRGYAz3UtVgHaEK?pid=Api&P=0&h=220',
                    'description' => '16GB RAM, 1TB SSD, Windows 11 Home'
                ],
                [
                    'id' => 12, 
                    'name' => 'lenovo think book 15 ', 
                    'price' => 2999.99, 
                    'image' => 'https://tse1.mm.bing.net/th/id/OIP.-_FMXk_MOnwf7n1ZX1IKlgHaFc?pid=Api&h=220&P=0',
                    'description' => '16GB RAM, 1TB SSD, Windows 11 Home'
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
                                <form method="POST" action="products.php">
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
    
        
    <div class="container mt-5">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="feature-box p-4">
                    <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                    <h4>Free Shipping</h4>
                    <p class="text-muted">Free shipping on all orders over $100</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box p-4">
                    <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                    <h4>2-Year Warranty</h4>
                    <p class="text-muted">All products come with 2-year warranty</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box p-4">
                    <i class="fas fa-headset fa-3x text-info mb-3"></i>
                    <h4>24/7 Support</h4>
                    <p class="text-muted">Round-the-clock customer support</p>
                </div>
            </div>
        </div>
    </div>

   
    
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
        
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href^="#"]');
            
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>