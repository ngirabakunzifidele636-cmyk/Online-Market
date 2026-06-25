<?php
session_start();
include 'config.php';

// Set your user ID manually (replace 1 with your actual user ID)
$user_id = 1; // Change this to your actual user ID

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['username'],
            'email' => $user['email']
        ];
        
        echo "Session set for user: " . $user['username'] . "<br>";
        echo "<a href='profile.php'>Go to Profile</a><br>";
        echo "<a href='debug_session.php'>Check Session</a>";
    } else {
        echo "User not found!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>