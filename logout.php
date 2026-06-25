<?php
include 'config.php';

// Destroy session and redirect to home
session_destroy();
header('Location: login.php');
exit;
?>