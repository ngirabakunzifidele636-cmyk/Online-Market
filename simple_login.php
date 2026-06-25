<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Use correct database credentials
$host = 'localhost';
$dbname = 'online_market';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['username']);
    $password_input = $_POST['password'];
    
    echo "<h1>Login Debug</h1>";
    echo "<p>Login attempt for: <strong>'$login'</strong></p>";
    
    // Show all users in database for debugging
    $stmt = $pdo->query("SELECT username, email FROM users");
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>All users in database:</strong></p>";
    echo "<ul>";
    foreach ($all_users as $user) {
        echo "<li>Username: '{$user['username']}', Email: '{$user['email']}'</li>";
    }
    echo "</ul>";
    
    // Find user by username or email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p style='color: green;'>✅ User found: '{$user['username']}' (Email: '{$user['email']}')</p>";
        
        // Debug password verification
        echo "<p>Input password: '$password_input'</p>";
        echo "<p>Stored hash: " . substr($user['password_hash'], 0, 20) . "...</p>";
        
        if (password_verify($password_input, $user['password_hash'])) {
            echo "<p style='color: green;'>✅ Password verified successfully!</p>";
            
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            
            echo "<p style='color: green;'>✅ Session created! Redirecting to products page...</p>";
            
            // Add small delay to see the messages
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'products.php';
                }, 2000);
            </script>";
            
        } else {
            echo "<p style='color: red;'>❌ Password verification failed</p>";
            echo "<p>Make sure you're using the correct password.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ User not found with username/email: '$login'</p>";
        echo "<p>Available users are shown above. Please use one of those usernames or emails.</p>";
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Simple Login</title>
        <style>
            body { font-family: Arial; max-width: 500px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #667eea; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
            .credentials { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h2>Simple Login (Debug Mode)</h2>
        
        <div class="credentials">
            <h3>Test Credentials:</h3>
            <p><strong>Username:</strong> testuser</p>
            <p><strong>Email:</strong> test@gmail.com</p>
            <p><strong>Password:</strong> password123</p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Username or Email *</label>
                <input type="text" name="username" required value="testuser">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required value="password123">
            </div>
            <button type="submit">Login & Debug</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="create_test_user.php">Create Test User</a> | 
            <a href="debug_users.php">Check All Users</a>
        </p>
    </body>
    </html>
    <?php
}
?>