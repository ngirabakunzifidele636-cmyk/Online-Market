<?php
session_start();

// Simple test dashboard
if (!isset($_SESSION['user_id'])) {
    die("Please login first. <a href='login.html'>Login</a>");
}

echo "<h1>Simple Test Dashboard</h1>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Username: " . ($_SESSION['username'] ?? 'Unknown') . "</p>";
echo "<p>If you can see this, sessions are working!</p>";
echo "<p><a href='dashboard.php'>Try Main Dashboard</a></p>";
echo "<p><a href='debug_orders.php'>Debug Orders</a></p>";
?>