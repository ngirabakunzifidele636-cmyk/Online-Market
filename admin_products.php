<?php
session_start();
require_once 'db_connection.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

try {
    $pdo = getDatabaseConnection();
    
    // Handle product actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_product'])) {
            // Add new product
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, stock_quantity, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['category_id'],
                $_POST['stock_quantity'],
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $success = "Product added successfully!";
        }
        elseif (isset($_POST['update_product'])) {
            // Update product
            $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category_id=?, stock_quantity=?, is_active=? WHERE id=?");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['category_id'],
                $_POST['stock_quantity'],
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['product_id']
            ]);
            $success = "Product updated successfully!";
        }
        elseif (isset($_POST['delete_product'])) {
            // Check if product has orders
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $check_stmt->execute([$_POST['product_id']]);
            $order_count = $check_stmt->fetchColumn();
            
            if ($order_count > 0) {
                // Product has orders, deactivate instead of delete
                $stmt = $pdo->prepare("UPDATE products SET is_active = FALSE WHERE id = ?");
                $stmt->execute([$_POST['product_id']]);
                $success = "Product has existing orders. It has been deactivated instead of deleted.";
            } else {
                // Product has no orders, safe to delete
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$_POST['product_id']]);
                $success = "Product deleted successfully!";
            }
        }
        elseif (isset($_POST['deactivate_product'])) {
            // Deactivate product
            $stmt = $pdo->prepare("UPDATE products SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
            $success = "Product deactivated successfully!";
        }
        elseif (isset($_POST['activate_product'])) {
            // Activate product
            $stmt = $pdo->prepare("UPDATE products SET is_active = TRUE WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
            $success = "Product activated successfully!";
        }
    }
    
    // Get all products with categories
    $products = $pdo->query("
        SELECT p.*, c.name as category_name,
               (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as order_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories for dropdown
    $categories = $pdo->query("SELECT * FROM categories WHERE is_active = TRUE ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get product to edit if requested
    $edit_product = null;
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <style>
        /* Reuse admin styles from dashboard */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Arial', sans-serif; }
        body { background: #f8f9fa; color: #333; }
        .admin-header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 15px 0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .admin-nav { display: flex; justify-content: space-between; align-items: center; }
        .admin-logo { font-size: 20px; font-weight: bold; }
        .admin-links a { color: white; text-decoration: none; margin-left: 20px; padding: 8px 16px; border-radius: 5px; transition: background 0.3s; }
        .admin-links a:hover { background: rgba(255,255,255,0.2); }
        .admin-links a.active { background: #667eea; }
        
        .admin-container { margin: 20px 0; }
        .admin-section { background: white; border-radius: 10px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; color: #555; }
        input, textarea, select { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #667eea; }
        textarea { resize: vertical; min-height: 100px; }
        .btn { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #5a6fd8; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219653; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        
        .products-table { width: 100%; border-collapse: collapse; }
        .products-table th, .products-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        .products-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white; }
        .status-active { background: #27ae60; }
        .status-inactive { background: #95a5a6; }
        .stock-low { color: #e74c3c; font-weight: bold; }
        .order-count { background: #3498db; color: white; padding: 2px 6px; border-radius: 8px; font-size: 10px; }
        .action-btns { display: flex; gap: 5px; flex-wrap: wrap; }
        .action-btn { padding: 4px 8px; font-size: 11px; text-decoration: none; border-radius: 3px; }
        
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        
        .product-actions { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="admin-nav">
                <div class="admin-logo">⚙️ Admin Panel</div>
                <div class="admin-links">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <a href="admin_products.php" class="active">Products</a>
                    <a href="admin_orders.php">Orders</a>
                    <a href="admin_users.php">Users</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="admin-container">
            <?php if(isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Add/Edit Product Form -->
            <div class="admin-section">
                <h2 class="section-title">
                    <?php echo $edit_product ? '✏️ Edit Product' : '➕ Add New Product'; ?>
                </h2>
                
                <form method="POST">
                    <?php if($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Product Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price ($) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required 
                                   value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($edit_product && $edit_product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                                   value="<?php echo $edit_product ? $edit_product['stock_quantity'] : '0'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="is_active" value="1" 
                                <?php echo ($edit_product && $edit_product['is_active']) || !$edit_product ? 'checked' : ''; ?>>
                            Product is active and visible to customers
                        </label>
                    </div>
                    
                    <div class="product-actions">
                        <?php if($edit_product): ?>
                            <button type="submit" name="update_product" class="btn btn-success">Update Product</button>
                            <a href="admin_products.php" class="btn">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
                        <?php endif; ?>
                        <!-- <a href="admin_reports.php?export=pdf&report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-warning">Report</a> -->
                          <a href="admin_reports.php" name="reports" class="btn btn-warning">Reports</a>
                    </div>
                </form>
            </div>
            
            <!-- Products List -->
            <div class="admin-section">
                <h2 class="section-title">📦 All Products (<?php echo count($products); ?>)</h2>
                
                <?php if(empty($products)): ?>
                    <p>No products found.</p>
                <?php else: ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Orders</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <?php if($product['description']): ?>
                                            <br><small style="color: #666;"><?php echo substr($product['description'], 0, 50); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td class="<?php echo $product['stock_quantity'] <= 5 ? 'stock-low' : ''; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </td>
                                    <td>
                                        <?php if($product['order_count'] > 0): ?>
                                            <span class="order-count" title="This product has <?php echo $product['order_count']; ?> order(s)">
                                                <?php echo $product['order_count']; ?> order(s)
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">No orders</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="admin_products.php?edit=<?php echo $product['id']; ?>" class="action-btn btn">Edit</a>
                                            
                                            <?php if($product['is_active']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" name="deactivate_product" class="action-btn btn-warning" 
                                                            onclick="return confirm('Deactivate this product? It will no longer be visible to customers.')">
                                                        Deactivate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" name="activate_product" class="action-btn btn-success">
                                                        Activate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if($product['order_count'] == 0): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" name="delete_product" class="action-btn btn-danger" 
                                                            onclick="return confirm('Are you sure you want to permanently delete this product? This action cannot be undone.')">
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" name="delete_product" class="action-btn btn-danger" 
                                                            onclick="return confirm('This product has <?php echo $product['order_count']; ?> order(s). It will be deactivated instead of deleted. Continue?')">
                                                        Delete/Deactivate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Management Tips -->
            <div class="admin-section">
                <h2 class="section-title">💡 Product Management Tips</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <h4>✅ Safe Actions</h4>
                        <ul style="font-size: 14px; color: #666;">
                            <li><strong>Edit:</strong> Update product details anytime</li>
                            <li><strong>Activate/Deactivate:</strong> Show/hide from customers</li>
                            <li><strong>Delete:</strong> Only allowed for products with no orders</li>
                        </ul>
                    </div>
                    <div>
                        <h4>⚠️ Protected Actions</h4>
                        <ul style="font-size: 14px; color: #666;">
                            <li>Products with orders cannot be deleted</li>
                            <li>They are automatically deactivated instead</li>
                            <li>This preserves order history and integrity</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>