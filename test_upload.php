<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_image'])) {
    echo "<h3>Upload Test Results</h3>";
    
    $file = $_FILES['test_image'];
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . " bytes<br>";
    echo "File temp path: " . $file['tmp_name'] . "<br>";
    echo "Upload error: " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Test the upload function directly
        $user_id = 1; // Test with user ID 1
        $result = handleProfileImageUpload($file, $user_id);
        
        echo "Upload result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "<br>";
        if ($result['success']) {
            echo "File saved to: " . $result['file_path'] . "<br>";
            echo "File exists: " . (file_exists($result['file_path']) ? 'YES' : 'NO') . "<br>";
        } else {
            echo "Error: " . $result['error'] . "<br>";
        }
    } else {
        echo "Upload failed with error code: " . $file['error'] . "<br>";
    }
    echo "<hr>";
}
?>

<h3>Test Image Upload</h3>
<form method="POST" action="" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit">Test Upload</button>
</form>

<p><a href="debug_simple.php">Check Debug Again</a></p>