<?php
function getDatabaseConnection() {
    // XAMPP default settings
    $host = 'localhost';
    $dbname = 'online_market';
    $username = 'root';
    $password = '';
    
    // Alternative: If you created a specific user
    // $username = 'market_user';
    // $password = 'your_secure_password';
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        // Don't show detailed errors in production
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            // Development - show detailed error
            die("Database connection failed: " . $e->getMessage());
        } else {
            // Production - generic error
            die("Database connection failed. Please try again later.");
        }
    }
}
?>