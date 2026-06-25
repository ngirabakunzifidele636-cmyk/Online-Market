<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'online_market';
$username = 'your_username';
$password = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Find user
    $stmt = $pdo->prepare("SELECT id, username, first_name, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if ($user['email_verified']) {
            $_SESSION['message'] = 'Email is already verified. You can login.';
            $_SESSION['message_type'] = 'info';
            header('Location: login.html');
            exit();
        }
        
        // Generate new verification token
        $verificationToken = bin2hex(random_bytes(50));
        $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update user with new token
        $updateStmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_token_expires = ? WHERE id = ?");
        $updateStmt->execute([$verificationToken, $tokenExpires, $user['id']]);
        
        // Resend verification email
        require_once 'register.php'; // Include the sendVerificationEmail function
        if (sendVerificationEmail($email, $user['first_name'] ?: $user['username'], $verificationToken)) {
            $_SESSION['message'] = 'Verification email sent! Please check your inbox.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to send verification email. Please try again later.';
            $_SESSION['message_type'] = 'error';
        }
        
        header('Location: login.html');
        exit();
    } else {
        $_SESSION['message'] = 'Email not found in our system.';
        $_SESSION['message_type'] = 'error';
        header('Location: resend_verification_form.html');
        exit();
    }
}
?>

<!-- Resend Verification Form (resend_verification_form.html) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - Online Market</title>
    <style>
        /* Same styles as registration form */
    </style>
</head>
<body>
    <div class="container">
        <h2>Resend Verification Email</h2>
        <form method="POST" action="resend_verification.php">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn">Resend Verification</button>
            <div class="login-link">
                Remember your password? <a href="login.html">Sign In</a>
            </div>
        </form>
    </div>
</body>
</html>