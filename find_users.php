<?php
include 'config.php';

echo "<h3>Users in Database</h3>";

try {
    $stmt = $conn->prepare("SELECT id, username, email FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Action</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td><a href='manual_login.php?user_id=" . $user['id'] . "'>Login as this user</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No users found in database!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>