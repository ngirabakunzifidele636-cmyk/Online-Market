<?php
$host = 'localhost';
$dbname = 'online_market';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Token Check</h2>";
    
    $stmt = $conn->prepare("SELECT id, email, reset_token, reset_token_expires FROM users WHERE reset_token IS NOT NULL");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Token</th><th>Expires</th><th>Valid</th></tr>";
        foreach ($users as $user) {
            $is_valid = strtotime($user['reset_token_expires']) > time();
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>" . substr($user['reset_token'], 0, 20) . "...</td>";
            echo "<td>{$user['reset_token_expires']}</td>";
            echo "<td>" . ($is_valid ? 'YES' : 'NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No reset tokens found in database.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>