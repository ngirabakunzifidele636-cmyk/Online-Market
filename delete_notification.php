<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit();
}

$notification_id = $_POST['notification_id'];
$user_id = $_SESSION['user_id'];

// Delete notification
if (deleteNotification($notification_id, $user_id)) {
    echo json_encode(['success' => true, 'message' => 'Notification deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
}
?>