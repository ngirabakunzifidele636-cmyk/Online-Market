<?php
echo "<h2>PHPMailer Test</h2>";

// Test different paths
$paths = [
    'PHPMailer/PHPMailer.php',
    './PHPMailer/PHPMailer.php', 
    __DIR__ . '/PHPMailer/PHPMailer.php'
];

foreach ($paths as $path) {
    echo "Checking: $path - ";
    if (file_exists($path)) {
        echo "<span style='color: green;'>EXISTS</span><br>";
        
        // Try to load the file
        require_once $path;
        require_once str_replace('PHPMailer.php', 'SMTP.php', $path);
        require_once str_replace('PHPMailer.php', 'Exception.php', $path);
        
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "<span style='color: green;'>✓ PHPMailer class loaded successfully</span><br>";
        } else {
            echo "<span style='color: red;'>✗ PHPMailer class NOT loaded</span><br>";
        }
    } else {
        echo "<span style='color: red;'>NOT FOUND</span><br>";
    }
    echo "<br>";
}

echo "<h3>Current Directory: " . __DIR__ . "</h3>";
?>