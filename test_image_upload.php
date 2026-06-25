<?php
include 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = $_SESSION['user_id'];
echo "User ID: $user_id<br>";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_image'])) {
    echo "<h3>Upload Test Results</h3>";
    
    $file = $_FILES['test_image'];
    $upload_result = handleProfileImageUpload($file, $user_id);
    
    if ($upload_result['success']) {
        echo "✅ Upload successful!<br>";
        echo "File saved to: " . $upload_result['file_path'] . "<br>";
        echo "File exists: " . (file_exists($upload_result['file_path']) ? 'YES' : 'NO') . "<br>";
        
        // Update database
        try {
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->execute([$upload_result['file_path'], $user_id]);
            echo "✅ Database updated!<br>";
            
            // Check what's in database now
            $check_stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
            $check_stmt->execute([$user_id]);
            $user = $check_stmt->fetch();
            echo "Profile image in database: " . ($user['profile_image'] ?? 'NULL') . "<br>";
            
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Upload failed: " . $upload_result['error'] . "<br>";
    }
    echo "<hr>";
}
?>

<h3>Test Image Upload for User ID: <?= $user_id ?></h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit">Test Upload</button>
</form>

<p><a href="profile.php">Back to Profile</a></p>