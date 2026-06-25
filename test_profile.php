<?php
include 'config.php';

echo "<h3>Profile Test</h3>";

if (isset($_SESSION['user_id'])) {
    echo "User ID in session: " . $_SESSION['user_id'] . "<br>";
    echo "<a href='profile.php'>Go to Profile</a><br>";
    echo "<a href='profile_edit.php'>Go to Edit Profile</a><br>";
} else {
    echo "No user ID in session. <a href='login.php'>Login first</a><br>";
    
    echo "<h4>Current Session:</h4>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}
?>