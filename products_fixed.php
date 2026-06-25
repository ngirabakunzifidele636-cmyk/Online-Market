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
$error = '';

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    try {
        // Get product details
        $product_stmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $total_price = $product['price'] * $quantity;
            
            // Check if item already in cart
            $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $check_stmt->execute([$user_id, $product_id]);
            $existing_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_item) {
                // Update quantity
                $new_quantity = $existing_item['quantity'] + $quantity;
                $new_total = $product['price'] * $new_quantity;
                
                $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, total_price = ? WHERE id = ?");
                $update_stmt->execute([$new_quantity, $new_total, $existing_item['id']]);
            } else {
                // Add new item
                $insert_stmt = $conn->prepare("
                    INSERT INTO cart (user_id, product_id, product_name, product_price, quantity, total_price) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->execute([
                    $user_id, 
                    $product_id, 
                    $product['name'], 
                    $product['price'], 
                    $quantity, 
                    $total_price
                ]);
            }
            
            $success = "Product added to cart successfully!";
        } else {
            $error = "Product not found!";
        }
        
    } catch (PDOException $e) {
        $error = "Error adding to cart: " . $e->getMessage();
    }
}

// Get all products
try {
    $products = $conn->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4"><i class="fas fa-shopping-bag me-2"></i>Our Products</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?= getProductImage($product['name']) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($product['description'] ?? 'No description available') ?></p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="h4 text-primary">$<?= number_format($product['price'], 2) ?></span>
                                        <span class="badge bg-secondary"><?= ucfirst($product['category'] ?? 'General') ?></span>
                                    </div>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <select name="quantity" class="form-select" style="width: 80px;">
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <option value="<?= $i ?>"><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="submit" name="add_to_cart" class="btn btn-primary flex-grow-1">
                                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h3>No Products Available</h3>
                    <p class="text-muted">Products will be added soon.</p>
                    <a href="setup_cart_system.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Sample Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Function to get product image (same as in order_details.php)
function getProductImage($product_name) {
    $product_images = [
        'wireless headphones' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'headphones' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'bluetooth' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'smartphone' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=300&fit=crop',
        'phone case' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop',
        'case' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop',
        'laptop' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=300&fit=crop',
        'sleeve' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=300&fit=crop',
        'usb' => 'https://images.unsplash.com/photo-1580522154071-c6ca47a859ad?w=400&h=300&fit=crop',
        'cable' => 'https://images.unsplash.com/photo-1580522154071-c6ca47a859ad?w=400&h=300&fit=crop',
        'screen protector' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop',
        'protector' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop'
    ];
    
    $product_name_lower = strtolower($product_name);
    
    foreach ($product_images as $key => $image) {
        if (strpos($product_name_lower, $key) !== false) {
            return $image;
        }
    }
    
    $fallback_images = [
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=300&fit=crop'
    ];
    
    return $fallback_images[array_rand($fallback_images)];
}
?>