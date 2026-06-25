<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simple database connection
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
    // Get basic form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $first_name = trim($_POST['first_name'] ?? '');
    
    echo "<h1>Registration Debug</h1>";
    echo "<p>Username: $username</p>";
    echo "<p>Email: $email</p>";
    echo "<p>First Name: $first_name</p>";
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        die("All fields are required");
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        die("Username or email already exists");
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, email_verified) VALUES (?, ?, ?, ?, TRUE)");
        $stmt->execute([$username, $email, $passwordHash, $first_name]);
        
        $userId = $pdo->lastInsertId();
        
        echo "<p style='color: green;'>✅ User registered successfully! ID: $userId</p>";
        
        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first_name;
        
        echo "<p style='color: green;'>✅ Session created successfully!</p>";
        
        // Redirect to products
        echo "<p>Redirecting to products page...</p>";
        header("Location: products.php");
        exit();
        
    } catch(PDOException $e) {
        echo "<p style='color: red;'>❌ Registration failed: " . $e->getMessage() . "</p>";
    }
} else {
    // Show form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Simple Register</title>
        <style>
            body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        </style>
    </head>
    <body>
        <h2>Simple Registration (Debug)</h2>
        <form method="POST">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name">
            </div>
            <button type="submit">Register & Debug</button>
        </form>
        <p><a href="debug_users.php">Check Users</a> | <a href="login.html">Login</a></p>
    </body>
    </html>
    <?php
}
?>