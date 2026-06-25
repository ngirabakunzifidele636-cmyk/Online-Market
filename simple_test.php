<?php
session_start();
echo "<h1>Simple Test</h1>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not logged in') . "</p>";
echo "<p>If you see this, PHP is working!</p>";
?>