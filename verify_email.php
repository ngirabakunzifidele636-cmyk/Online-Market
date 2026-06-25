<?php
session_start();
// On successful verification:
header('Location: login.html?verified=' . urlencode($user['email']));
exit();

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

// Check if token is provided
if (!isset($_GET['token'])) {
    $_SESSION['message'] = 'Invalid verification link.';
    $_SESSION['message_type'] = 'error';
    header('Location: login.html');
    exit();
}

$token = $_GET['token'];

// Find user with this token
$stmt = $pdo->prepare("SELECT id, username, email, verification_token_expires FROM users WHERE verification_token = ? AND email_verified = FALSE");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Check if token is expired
    if (strtotime($user['verification_token_expires']) < time()) {
        $_SESSION['message'] = 'Verification link has expired. Please register again.';
        $_SESSION['message_type'] = 'error';
        
        // Delete the expired registration
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$user['id']]);
        
        header('Location: register.html');
        exit();
    }
    
    // Verify the email
    $updateStmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, verified_at = NOW(), verification_token = NULL, verification_token_expires = NULL WHERE id = ?");
    $updateStmt->execute([$user['id']]);
    
    $_SESSION['message'] = 'Email verified successfully! You can now login to your account.';
    $_SESSION['message_type'] = 'success';
    $_SESSION['verified_email'] = $user['email'];
    
    header('Location: login.html');
    exit();
    
} else {
    $_SESSION['message'] = 'Invalid or already used verification link.';
    $_SESSION['message_type'] = 'error';
    header('Location: login.html');
    exit();
}
?>