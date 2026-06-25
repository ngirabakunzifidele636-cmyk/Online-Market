<?php
include 'config.php';

echo "<h3>Simple Debug - No Login Required</h3>";

// Check upload directory
echo "1. Upload directory: " . UPLOAD_DIR . "<br>";
echo "2. Directory exists: " . (file_exists(UPLOAD_DIR) ? 'YES' : 'NO') . "<br>";
echo "3. Directory writable: " . (is_writable(UPLOAD_DIR) ? 'YES' : 'NO') . "<br>";

// Check if profile_image column exists
try {
    $stmt = $conn->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "4. Profile image column exists: " . (in_array('profile_image', $columns) ? 'YES' : 'NO') . "<br>";
    
    if (in_array('profile_image', $columns)) {
        echo "5. Column type: ";
        $stmt2 = $conn->prepare("SHOW COLUMNS FROM users LIKE 'profile_image'");
        $stmt2->execute();
        $col_info = $stmt2->fetch();
        echo $col_info['Type'] . "<br>";
    }
} catch (Exception $e) {
    echo "4. Error checking columns: " . $e->getMessage() . "<br>";
}

// Check if upload functions exist
echo "6. Upload function exists: " . (function_exists('handleProfileImageUpload') ? 'YES' : 'NO') . "<br>";

// List files in upload directory
if (file_exists(UPLOAD_DIR)) {
    echo "7. Files in upload directory:<br>";
    $files = scandir(UPLOAD_DIR);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "&nbsp;&nbsp;- $file<br>";
        }
    }
    if (count($files) <= 2) {
        echo "&nbsp;&nbsp;No files found<br>";
    }
}

echo "<br><strong>Next steps:</strong><br>";
echo "1. If directory doesn't exist or isn't writable, check permissions<br>";
echo "2. If column doesn't exist, run the SQL to add it<br>";
echo "3. If no upload function, add it to config.php<br>";
?>