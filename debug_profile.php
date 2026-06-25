<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Debug Profile Information</h2>";
echo "<pre>";
echo "User ID: " . $user_id . "\n";
echo "Profile Image Column Exists: " . (array_key_exists('profile_image', $user) ? 'YES' : 'NO') . "\n";
echo "Profile Image Value: " . ($user['profile_image'] ?? 'NULL') . "\n";
echo "File Exists: " . (file_exists($user['profile_image'] ?? '') ? 'YES' : 'NO') . "\n";
echo "Upload Directory: " . UPLOAD_DIR . "\n";
echo "Directory Exists: " . (file_exists(UPLOAD_DIR) ? 'YES' : 'NO') . "\n";
echo "Directory Writable: " . (is_writable(UPLOAD_DIR) ? 'YES' : 'NO') . "\n";

// List files in upload directory
if (file_exists(UPLOAD_DIR)) {
    echo "Files in upload directory:\n";
    $files = scandir(UPLOAD_DIR);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo " - $file\n";
        }
    }
}
echo "</pre>";
?>