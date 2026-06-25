<?php
session_start();
include 'config.php';

// Get user ID from URL or form
$user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? null;

if ($user_id) {
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
            
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
            echo "<h4>✅ Login Successful!</h4>";
            echo "Logged in as: <strong>" . $user['username'] . "</strong> (ID: " . $user['id'] . ")<br>";
            echo "Email: " . $user['email'] . "<br><br>";
            echo "<a href='profile.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Profile</a> ";
            echo "<a href='debug_session.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Check Session</a>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
            echo "User not found with ID: " . $user_id;
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Show form to manually enter user ID
echo "<h3>Manual Login</h3>";
echo "<form method='POST'>";
echo "User ID: <input type='number' name='user_id' required> ";
echo "<button type='submit'>Login</button>";
echo "</form>";
echo "<br>";
echo "<a href='find_users.php'>Find All Users</a>";
?>