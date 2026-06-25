<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// Mark all notifications as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

// Redirect back to previous page
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit();