<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

if (isset($_GET['id'])) {
    // Mark notification as read
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
}

// Redirect back or to default page
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit();