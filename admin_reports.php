<?php
session_start();
require_once 'db_connection.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

// Handle exports
if (isset($_GET['export'])) {
    require_once 'export_reports.php';
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // Get date range from filters
    $start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
    $end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
    $report_type = $_GET['report_type'] ?? 'sales_summary';
    
    // Sales Summary Report
    if ($report_type === 'sales_summary') {
        $sales_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
                SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as pending_revenue,
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_orders
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
        ");
        $sales_stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $sales_data = $sales_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Daily sales trend
        $daily_sales = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as daily_revenue
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $daily_sales->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $daily_sales_data = $daily_sales->fetchAll(PDO::FETCH_ASSOC);
        
        // Top selling products for this period
        $top_products = $pdo->prepare("
            SELECT 
                p.name as product_name,
                SUM(oi.quantity) as units_sold,
                SUM(oi.total_price) as revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY revenue DESC
            LIMIT 10
        ");
        $top_products->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $top_products_data = $top_products->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Product Performance Report
    if ($report_type === 'product_performance') {
        $product_performance = $pdo->prepare("
            SELECT 
                p.name as product_name,
                p.category_id,
                c.name as category_name,
                COUNT(oi.id) as units_sold,
                SUM(oi.total_price) as revenue,
                AVG(oi.product_price) as avg_price,
                p.stock_quantity,
                p.price as current_price,
                p.is_active
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
            LEFT JOIN categories c ON p.category_id = c.id
            GROUP BY p.id
            ORDER BY revenue DESC
        ");
        $product_performance->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $product_data = $product_performance->fetchAll(PDO::FETCH_ASSOC);
        
        // Product performance summary
        $product_summary = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT p.id) as total_products,
                SUM(oi.quantity) as total_units_sold,
                SUM(oi.total_price) as total_revenue,
                COUNT(DISTINCT oi.product_id) as products_sold
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        ");
        $product_summary->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $product_summary_data = $product_summary->fetch(PDO::FETCH_ASSOC);
    }
    
    // Customer Reports
    if ($report_type === 'customer_analysis') {
        $customer_analysis = $pdo->prepare("
            SELECT 
                u.id,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                COUNT(o.id) as order_count,
                SUM(o.total_amount) as total_spent,
                MAX(o.created_at) as last_order_date,
                u.created_at as join_date
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            WHERE (o.created_at BETWEEN ? AND ? OR o.id IS NULL)
            GROUP BY u.id
            HAVING order_count > 0
            ORDER BY total_spent DESC
        ");
        $customer_analysis->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $customer_data = $customer_analysis->fetchAll(PDO::FETCH_ASSOC);
        
        // Customer statistics
        $customer_stats = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT u.id) as total_customers,
                COUNT(DISTINCT o.user_id) as active_customers,
                AVG(order_data.avg_order_value) as avg_customer_value
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.created_at BETWEEN ? AND ?
            LEFT JOIN (
                SELECT user_id, AVG(total_amount) as avg_order_value 
                FROM orders 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY user_id
            ) as order_data ON u.id = order_data.user_id
        ");
        $customer_stats->execute([
            $start_date . ' 00:00:00', $end_date . ' 23:59:59',
            $start_date . ' 00:00:00', $end_date . ' 23:59:59'
        ]);
        $customer_stats_data = $customer_stats->fetch(PDO::FETCH_ASSOC);
        
        // New customers
        $new_customers = $pdo->prepare("
            SELECT COUNT(*) as new_customers
            FROM users 
            WHERE created_at BETWEEN ? AND ?
        ");
        $new_customers->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $new_customers_count = $new_customers->fetchColumn();
    }
    
    // Inventory Report
    if ($report_type === 'inventory') {
        $inventory_report = $pdo->query("
            SELECT 
                p.name,
                p.stock_quantity,
                p.price,
                c.name as category_name,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as times_ordered,
                p.is_active,
                p.low_stock_threshold
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.stock_quantity ASC, times_ordered DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $low_stock_count = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold AND stock_quantity > 0 AND is_active = TRUE")->fetchColumn();
        $out_of_stock_count = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0 AND is_active = TRUE")->fetchColumn();
        $total_inventory_value = $pdo->query("SELECT SUM(price * stock_quantity) as total_value FROM products WHERE is_active = TRUE")->fetchColumn();
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
    <title>Reports - Admin Panel</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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
        
        .filters { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; margin-bottom: 20px; align-items: end; }
        .form-group { margin-bottom: 0; }
        label { display: block; margin-bottom: 5px; font-weight: 500; color: #555; }
        input, select { width: 100%; padding: 8px 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; }
        .btn { background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #5a6fd8; }
        .btn-outline { background: white; border: 2px solid #667eea; color: #667eea; }
        .btn-outline:hover { background: #667eea; color: white; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219653; }
        .btn-warning { background: #f39c12; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-icon { font-size: 30px; margin-bottom: 10px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .stat-label { color: #666; font-size: 14px; }
        
        .report-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .report-table th, .report-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        .report-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        
        .chart-container { position: relative; height: 300px; margin: 20px 0; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        .status-active { background: #27ae60; }
        .status-inactive { background: #95a5a6; }
        .stock-low { color: #e74c3c; font-weight: bold; }
        .stock-out { color: #c0392b; font-weight: bold; background: #f8d7da; padding: 2px 6px; border-radius: 4px; }
        
        .report-actions { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
        .report-period { color: #666; font-size: 14px; margin-bottom: 15px; }
        
        @media print {
            .admin-header, .filters, .report-actions { display: none !important; }
            .admin-section { box-shadow: none; border: 1px solid #ddd; }
            .stat-card { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="admin-nav">
                <div class="admin-logo">⚙️ Admin Panel</div>
                <div class="admin-links">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <a href="admin_products.php">Products</a>
                    <a href="admin_orders.php">Orders</a>
                    <a href="admin_users.php">Users</a>
                    <a href="admin_reports.php" class="active">Reports</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="admin-container">
            <!-- Report Filters -->
            <div class="admin-section">
                <h2 class="section-title">📊 Reports & Analytics</h2>
                
                <form method="GET" class="filters">
                    <div class="form-group">
                        <label for="report_type">Report Type</label>
                        <select id="report_type" name="report_type">
                            <option value="sales_summary" <?php echo $report_type === 'sales_summary' ? 'selected' : ''; ?>>Sales Summary</option>
                            <option value="product_performance" <?php echo $report_type === 'product_performance' ? 'selected' : ''; ?>>Product Performance</option>
                            <option value="customer_analysis" <?php echo $report_type === 'customer_analysis' ? 'selected' : ''; ?>>Customer Analysis</option>
                            <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn" name="generate_report">Generate Report</button>
                    </div>
                </form>
                
                <div class="report-period">
                    <strong>Report Period:</strong> <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?>
                </div>
                
                <div class="report-actions">
                 <a href="print_report.php?report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-warning" target="_blank">📄 Export PDF</a>
                <a href="admin_reports.php?export=csv&report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success">📊 Export CSV</a>
              <button onclick="window.print()" class="btn btn-outline">🖨️ Print Report</button>
           </div>
            
            <!-- Sales Summary Report -->
            <?php if($report_type === 'sales_summary'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-number"><?php echo $sales_data['total_orders'] ?? 0; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-number">$<?php echo number_format($sales_data['total_revenue'] ?? 0, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-number">$<?php echo number_format($sales_data['avg_order_value'] ?? 0, 2); ?></div>
                        <div class="stat-label">Average Order Value</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?php echo $sales_data['paid_orders'] ?? 0; ?></div>
                        <div class="stat-label">Paid Orders</div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <h2 class="section-title">📈 Sales Trend (Daily)</h2>
                    <?php if(!empty($daily_sales_data)): ?>
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                        <script>
                            const salesCtx = document.getElementById('salesChart').getContext('2d');
                            const salesChart = new Chart(salesCtx, {
                                type: 'line',
                                data: {
                                    labels: <?php echo json_encode(array_column($daily_sales_data, 'date')); ?>,
                                    datasets: [{
                                        label: 'Daily Revenue',
                                        data: <?php echo json_encode(array_column($daily_sales_data, 'daily_revenue')); ?>,
                                        borderColor: '#667eea',
                                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                        tension: 0.4,
                                        fill: true
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value) {
                                                    return '$' + value;
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        </script>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No sales data available for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Top Products -->
                <?php if(!empty($top_products_data)): ?>
                <div class="admin-section">
                    <h2 class="section-title">🏆 Top Selling Products</h2>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_products_data as $product): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                    <td><?php echo $product['units_sold'] ?? 0; ?></td>
                                    <td>$<?php echo number_format($product['revenue'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
            <!-- Product Performance Report -->
            <?php if($report_type === 'product_performance'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-number"><?php echo $product_summary_data['total_products'] ?? 0; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-number"><?php echo $product_summary_data['products_sold'] ?? 0; ?></div>
                        <div class="stat-label">Products Sold</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-number"><?php echo $product_summary_data['total_units_sold'] ?? 0; ?></div>
                        <div class="stat-label">Units Sold</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-number">$<?php echo number_format($product_summary_data['total_revenue'] ?? 0, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <h2 class="section-title">📋 Product Performance Details</h2>
                    <?php if(!empty($product_data)): ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Avg Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($product_data as $product): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td><?php echo $product['units_sold'] ?? 0; ?></td>
                                        <td>$<?php echo number_format($product['revenue'] ?? 0, 2); ?></td>
                                        <td>$<?php echo number_format($product['avg_price'] ?? 0, 2); ?></td>
                                        <td class="<?php echo $product['stock_quantity'] == 0 ? 'stock-out' : ($product['stock_quantity'] <= 5 ? 'stock-low' : ''); ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No product sales data available for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Customer Analysis Report -->
            <?php if($report_type === 'customer_analysis'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number"><?php echo $customer_stats_data['total_customers'] ?? 0; ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-number"><?php echo $customer_stats_data['active_customers'] ?? 0; ?></div>
                        <div class="stat-label">Active Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🆕</div>
                        <div class="stat-number"><?php echo $new_customers_count ?? 0; ?></div>
                        <div class="stat-label">New Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-number">$<?php echo number_format($customer_stats_data['avg_customer_value'] ?? 0, 2); ?></div>
                        <div class="stat-label">Avg Customer Value</div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <h2 class="section-title">👥 Customer Analysis</h2>
                    <?php if(!empty($customer_data)): ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Last Order</th>
                                    <th>Member Since</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($customer_data as $customer): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($customer['username']); ?></strong>
                                            <?php if($customer['first_name'] || $customer['last_name']): ?>
                                                <br><small><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo $customer['order_count']; ?></td>
                                        <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                        <td><?php echo $customer['last_order_date'] ? date('M j, Y', strtotime($customer['last_order_date'])) : 'Never'; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($customer['join_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No customer data available for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Inventory Report -->
            <?php if($report_type === 'inventory'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-number"><?php echo count($inventory_report); ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-number"><?php echo $low_stock_count; ?></div>
                        <div class="stat-label">Low Stock Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">❌</div>
                        <div class="stat-number"><?php echo $out_of_stock_count; ?></div>
                        <div class="stat-label">Out of Stock</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-number">$<?php echo number_format($total_inventory_value, 2); ?></div>
                        <div class="stat-label">Total Inventory Value</div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <h2 class="section-title">📋 Inventory Status</h2>
                    <?php if(!empty($inventory_report)): ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Price</th>
                                    <th>Times Ordered</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($inventory_report as $product): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td class="<?php echo $product['stock_quantity'] == 0 ? 'stock-out' : ($product['stock_quantity'] <= ($product['low_stock_threshold'] ?? 5) ? 'stock-low' : ''); ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['times_ordered']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No inventory data available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>