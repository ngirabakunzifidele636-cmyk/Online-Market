<?php

require_once 'db_connection.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales_summary';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
    
    try {
        $pdo = getDatabaseConnection();
        
        if ($export_type === 'csv') {
            exportCSV($pdo, $report_type, $start_date, $end_date);
        } elseif ($export_type === 'pdf') {
            exportPDF($pdo, $report_type, $start_date, $end_date);
        }
        
    } catch(PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

function exportCSV($pdo, $report_type, $start_date, $end_date) {
    $filename = "{$report_type}_report_{$start_date}_to_{$end_date}.csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    switch($report_type) {
        case 'sales_summary':
            fputcsv($output, ['Sales Summary Report', 'Period: ' . $start_date . ' to ' . $end_date]);
            fputcsv($output, []); // Empty row
            
            // Summary stats
            $sales_stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
                FROM orders 
                WHERE created_at BETWEEN ? AND ?
            ");
            $sales_stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            $sales_data = $sales_stmt->fetch(PDO::FETCH_ASSOC);
            
            fputcsv($output, ['Metric', 'Value']);
            fputcsv($output, ['Total Orders', isset($sales_data['total_orders']) ? $sales_data['total_orders'] : 0]);
            fputcsv($output, ['Total Revenue', '$' . number_format(isset($sales_data['total_revenue']) ? $sales_data['total_revenue'] : 0, 2)]);
            fputcsv($output, ['Average Order Value', '$' . number_format(isset($sales_data['avg_order_value']) ? $sales_data['avg_order_value'] : 0, 2)]);
            fputcsv($output, ['Paid Revenue', '$' . number_format(isset($sales_data['paid_revenue']) ? $sales_data['paid_revenue'] : 0, 2)]);
            fputcsv($output, ['Paid Orders', isset($sales_data['paid_orders']) ? $sales_data['paid_orders'] : 0]);
            fputcsv($output, []); // Empty row
            
            // Daily sales
            fputcsv($output, ['Daily Sales Breakdown']);
            fputcsv($output, ['Date', 'Orders', 'Revenue']);
            
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
            
            while ($row = $daily_sales->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['date'],
                    $row['order_count'],
                    '$' . number_format($row['daily_revenue'], 2)
                ]);
            }
            break;
            
        case 'product_performance':
            fputcsv($output, ['Product Performance Report', 'Period: ' . $start_date . ' to ' . $end_date]);
            fputcsv($output, []); // Empty row
            fputcsv($output, ['Product', 'Category', 'Units Sold', 'Revenue', 'Average Price', 'Stock', 'Status']);
            
            $products = $pdo->prepare("
                SELECT 
                    p.name as product_name,
                    c.name as category_name,
                    COUNT(oi.id) as units_sold,
                    SUM(oi.total_price) as revenue,
                    AVG(oi.product_price) as avg_price,
                    p.stock_quantity,
                    p.is_active
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
                LEFT JOIN categories c ON p.category_id = c.id
                GROUP BY p.id
                ORDER BY revenue DESC
            ");
            $products->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            
            while ($row = $products->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['product_name'],
                    isset($row['category_name']) ? $row['category_name'] : 'Uncategorized',
                    isset($row['units_sold']) ? $row['units_sold'] : 0,
                    '$' . number_format(isset($row['revenue']) ? $row['revenue'] : 0, 2),
                    '$' . number_format(isset($row['avg_price']) ? $row['avg_price'] : 0, 2),
                    $row['stock_quantity'],
                    $row['is_active'] ? 'Active' : 'Inactive'
                ]);
            }
            break;
            
        case 'customer_analysis':
            fputcsv($output, ['Customer Analysis Report', 'Period: ' . $start_date . ' to ' . $end_date]);
            fputcsv($output, []); // Empty row
            fputcsv($output, ['Customer', 'Email', 'Orders', 'Total Spent', 'Last Order', 'Member Since']);
            
            $customers = $pdo->prepare("
                SELECT 
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
            $customers->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            
            while ($row = $customers->fetch(PDO::FETCH_ASSOC)) {
                $customer_name = ($row['first_name'] || $row['last_name']) 
                    ? $row['first_name'] . ' ' . $row['last_name'] 
                    : $row['username'];
                    
                fputcsv($output, [
                    $customer_name,
                    $row['email'],
                    $row['order_count'],
                    '$' . number_format($row['total_spent'], 2),
                    $row['last_order_date'] ? date('Y-m-d', strtotime($row['last_order_date'])) : 'Never',
                    date('Y-m-d', strtotime($row['join_date']))
                ]);
            }
            break;
            
        case 'inventory':
            fputcsv($output, ['Inventory Report', 'Generated: ' . date('Y-m-d H:i:s')]);
            fputcsv($output, []); // Empty row
            fputcsv($output, ['Product', 'Category', 'Stock Quantity', 'Price', 'Times Ordered', 'Status', 'Stock Status']);
            
            $inventory = $pdo->query("
                SELECT 
                    p.name,
                    c.name as category_name,
                    p.stock_quantity,
                    p.price,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as times_ordered,
                    p.is_active,
                    p.low_stock_threshold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.stock_quantity ASC, times_ordered DESC
            ");
            
            while ($row = $inventory->fetch(PDO::FETCH_ASSOC)) {
                $stock_status = '';
                if ($row['stock_quantity'] == 0) {
                    $stock_status = 'Out of Stock';
                } elseif ($row['stock_quantity'] <= (isset($row['low_stock_threshold']) ? $row['low_stock_threshold'] : 5)) {
                    $stock_status = 'Low Stock';
                } else {
                    $stock_status = 'In Stock';
                }
                
                fputcsv($output, [
                    $row['name'],
                    isset($row['category_name']) ? $row['category_name'] : 'Uncategorized',
                    $row['stock_quantity'],
                    '$' . number_format($row['price'], 2),
                    $row['times_ordered'],
                    $row['is_active'] ? 'Active' : 'Inactive',
                    $stock_status
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}

function exportPDF($pdo, $report_type, $start_date, $end_date) {
    // Simple HTML to PDF approach - for production, use a library like TCPDF
    $html = generatePDFHTML($pdo, $report_type, $start_date, $end_date);
    
    // Set headers for PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . $start_date . '_to_' . $end_date . '.pdf"');
    
    // For now, we'll output HTML that users can print as PDF
    // In production, you would use: TCPDF, DomPDF, or mpdf
    echo $html;
    exit;
}

function generatePDFHTML($pdo, $report_type, $start_date, $end_date) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo ucfirst(str_replace('_', ' ', $report_type)); ?> Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
            .header h1 { margin: 0; color: #2c3e50; }
            .period { color: #666; font-size: 16px; }
            .summary { margin: 20px 0; }
            .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
            .stat-card { border: 1px solid #ddd; padding: 15px; text-align: center; border-radius: 5px; }
            .stat-number { font-size: 24px; font-weight: bold; color: #667eea; }
            .stat-label { color: #666; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            th { background-color: #f8f9fa; font-weight: bold; }
            .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
            .stock-low { color: #e74c3c; font-weight: bold; }
            .stock-out { color: #c0392b; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1><?php echo ucfirst(str_replace('_', ' ', $report_type)); ?> Report</h1>
            <div class="period">Period: <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?></div>
            <div class="period">Generated: <?php echo date('F j, Y g:i A'); ?></div>
        </div>
        
        <?php
        switch($report_type) {
            case 'sales_summary':
                generateSalesPDF($pdo, $start_date, $end_date);
                break;
            case 'product_performance':
                generateProductPDF($pdo, $start_date, $end_date);
                break;
            case 'customer_analysis':
                generateCustomerPDF($pdo, $start_date, $end_date);
                break;
            case 'inventory':
                generateInventoryPDF($pdo);
                break;
        }
        ?>
        
        <div class="footer">
            <p>Generated by Online Market Admin Panel</p>
            <p>Page generated on <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

function generateSalesPDF($pdo, $start_date, $end_date) {
    $sales_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
    ");
    $sales_stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $sales_data = $sales_stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_orders = isset($sales_data['total_orders']) ? $sales_data['total_orders'] : 0;
    $total_revenue = isset($sales_data['total_revenue']) ? $sales_data['total_revenue'] : 0;
    $avg_order_value = isset($sales_data['avg_order_value']) ? $sales_data['avg_order_value'] : 0;
    $paid_orders = isset($sales_data['paid_orders']) ? $sales_data['paid_orders'] : 0;
    ?>
    
    <div class="summary-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_orders; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">$<?php echo number_format($avg_order_value, 2); ?></div>
            <div class="stat-label">Average Order Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $paid_orders; ?></div>
            <div class="stat-label">Paid Orders</div>
        </div>
    </div>
    
    <h3>Daily Sales Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Orders</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php
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
            
            while ($row = $daily_sales->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>
                    <td>{$row['date']}</td>
                    <td>{$row['order_count']}</td>
                    <td>$" . number_format($row['daily_revenue'], 2) . "</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    <?php
}

function generateProductPDF($pdo, $start_date, $end_date) {
    ?>
    <h3>Product Performance</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Units Sold</th>
                <th>Revenue</th>
                <th>Stock</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $products = $pdo->prepare("
                SELECT 
                    p.name as product_name,
                    c.name as category_name,
                    COUNT(oi.id) as units_sold,
                    SUM(oi.total_price) as revenue,
                    p.stock_quantity,
                    p.is_active
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
                LEFT JOIN categories c ON p.category_id = c.id
                GROUP BY p.id
                ORDER BY revenue DESC
            ");
            $products->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            
            while ($row = $products->fetch(PDO::FETCH_ASSOC)) {
                $stock_class = '';
                if ($row['stock_quantity'] == 0) {
                    $stock_class = 'stock-out';
                } elseif ($row['stock_quantity'] <= 5) {
                    $stock_class = 'stock-low';
                }
                
                $units_sold = isset($row['units_sold']) ? $row['units_sold'] : 0;
                $revenue = isset($row['revenue']) ? $row['revenue'] : 0;
                $category_name = isset($row['category_name']) ? $row['category_name'] : 'Uncategorized';
                
                echo "<tr>
                    <td>{$row['product_name']}</td>
                    <td>{$category_name}</td>
                    <td>{$units_sold}</td>
                    <td>$" . number_format($revenue, 2) . "</td>
                    <td class='{$stock_class}'>{$row['stock_quantity']}</td>
                    <td>" . ($row['is_active'] ? 'Active' : 'Inactive') . "</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    <?php
}

function generateCustomerPDF($pdo, $start_date, $end_date) {
    ?>
    <h3>Customer Analysis</h3>
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Email</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Last Order</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $customers = $pdo->prepare("
                SELECT 
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    COUNT(o.id) as order_count,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.created_at) as last_order_date
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id
                WHERE (o.created_at BETWEEN ? AND ? OR o.id IS NULL)
                GROUP BY u.id
                HAVING order_count > 0
                ORDER BY total_spent DESC
                LIMIT 50
            ");
            $customers->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            
            while ($row = $customers->fetch(PDO::FETCH_ASSOC)) {
                $customer_name = ($row['first_name'] || $row['last_name']) 
                    ? $row['first_name'] . ' ' . $row['last_name'] 
                    : $row['username'];
                    
                echo "<tr>
                    <td>{$customer_name}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['order_count']}</td>
                    <td>$" . number_format($row['total_spent'], 2) . "</td>
                    <td>" . ($row['last_order_date'] ? date('M j, Y', strtotime($row['last_order_date'])) : 'Never') . "</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    <?php
}

function generateInventoryPDF($pdo) {
    ?>
    <h3>Inventory Status</h3>
    <table>
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
            <?php
            $inventory = $pdo->query("
                SELECT 
                    p.name,
                    c.name as category_name,
                    p.stock_quantity,
                    p.price,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as times_ordered,
                    p.is_active
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.stock_quantity ASC, times_ordered DESC
            ");
            
            while ($row = $inventory->fetch(PDO::FETCH_ASSOC)) {
                $stock_class = '';
                if ($row['stock_quantity'] == 0) {
                    $stock_class = 'stock-out';
                } elseif ($row['stock_quantity'] <= 5) {
                    $stock_class = 'stock-low';
                }
                
                $category_name = isset($row['category_name']) ? $row['category_name'] : 'Uncategorized';
                
                echo "<tr>
                    <td>{$row['name']}</td>
                    <td>{$category_name}</td>
                    <td class='{$stock_class}'>{$row['stock_quantity']}</td>
                    <td>$" . number_format($row['price'], 2) . "</td>
                    <td>{$row['times_ordered']}</td>
                    <td>" . ($row['is_active'] ? 'Active' : 'Inactive') . "</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    <?php
}
?>