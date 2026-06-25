<?php
session_start();
echo "<h3>Session Debug</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check common session variables
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "user array: " . (isset($_SESSION['user']) ? 'SET' : 'NOT SET') . "<br>";
if (isset($_SESSION['user'])) {
    echo "user details: ";
    print_r($_SESSION['user']);
}
?>