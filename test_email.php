<?php
// test_email.php
require_once 'config.php';
require_once 'email_config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>";

// Test data
$test_order = [
    'order_number' => 'TEST' . date('YmdHis'),
    'total_amount' => 99.99,
    'subtotal' => 89.99,
    'tax_amount' => 7.20,
    'shipping_amount' => 2.80,
    'shipping_address' => "John Doe\n123 Test Street\nKigali, Rwanda 12345\nPhone: 0781234567\nEmail: test@example.com",
    'payment_method' => 'MoMo',
    'payment_status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
];

$test_items = [
    [
        'product_name' => 'Test Product 1',
        'quantity' => 2,
        'product_price' => 29.99,
        'total_price' => 59.98
    ],
    [
        'product_name' => 'Test Product 2',
        'quantity' => 1,
        'product_price' => 29.99,
        'total_price' => 29.99
    ]
];

$test_user = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'customer@example.com',
    'phone' => '0781234567'
];

echo "<h1 class='mb-4'>📧 Testing Email System</h1>";

// Send test customer email
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-primary text-white'>Customer Confirmation Email</div>";
echo "<div class='card-body'>";
$customer_email = EmailTemplates::orderConfirmation($test_order, $test_items, $test_user);
$customer_sent = EmailConfig::sendEmail('customer@test.com', 'Test Order Confirmation', $customer_email);
echo "<p>Status: " . ($customer_sent ? '<span class="badge bg-success">✅ Sent/Logged</span>' : '<span class="badge bg-danger">❌ Failed</span>') . "</p>";
echo "</div></div>";

// Send test admin email
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-warning'>Admin Notification Email</div>";
echo "<div class='card-body'>";
$admin_email = EmailTemplates::adminNewOrderNotification($test_order, $test_items, $test_user, 'MoMo', '781234567');
$admin_sent = EmailConfig::sendEmail('admin@techshop.com', 'Test Admin Notification', $admin_email);
echo "<p>Status: " . ($admin_sent ? '<span class="badge bg-success">✅ Sent/Logged</span>' : '<span class="badge bg-danger">❌ Failed</span>') . "</p>";
echo "</div></div>";

// Send test status update email
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-info text-white'>Status Update Email</div>";
echo "<div class='card-body'>";
$status_email = EmailTemplates::orderStatusUpdate($test_order, $test_user, 'pending', 'shipped');
$status_sent = EmailConfig::sendEmail('customer@test.com', 'Order Status Update', $status_email);
echo "<p>Status: " . ($status_sent ? '<span class="badge bg-success">✅ Sent/Logged</span>' : '<span class="badge bg-danger">❌ Failed</span>') . "</p>";
echo "</div></div>";

echo "<hr>";
echo "<div class='alert alert-info'>";
echo "<h5>📁 Email Logs Folder</h5>";
echo "<p>Check the <strong>email_logs</strong> folder for the generated email files.</p>";
echo "<p>Path: " . __DIR__ . "/email_logs/</p>";
echo "</div>";

echo "<a href='email_logs/' class='btn btn-primary' target='_blank'>View Email Logs Folder</a>";
echo " <a href='checkout.php' class='btn btn-success'>Go to Checkout</a>";

echo "</div></body></html>";
?>