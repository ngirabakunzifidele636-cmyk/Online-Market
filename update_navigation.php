<?php
echo "<h1>Updating Page Navigation</h1>";

$pages_to_update = [
    'dashboard.php',
    'cart.php',
    'checkout.php', 
    'order_tracking.php',
    'order_confirmation.php',
    'order_details.php',
    'admin_dashboard.php',
    'admin_products.php',
    'admin_orders.php',
    'admin_users.php',
    'admin_order_details.php'
];

foreach($pages_to_update as $page) {
    if(file_exists($page)) {
        echo "<p style='color: green;'>✅ $page - exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ $page - missing</p>";
    }
}

echo "<h2>Next Steps:</h2>";
echo "<p>1. Update each page to use the header.php and footer.php includes</p>";
echo "<p>2. Replace the existing header code with: <code>include 'header.php';</code></p>";
echo "<p>3. Replace the footer with: <code>include 'footer.php';</code></p>";
echo "<p>4. Set the page title at the top: <code>\$page_title = 'Page Title';</code></p>";

echo "<h2 style='color: green;'>🎉 Navigation system ready to implement!</h2>";
?>