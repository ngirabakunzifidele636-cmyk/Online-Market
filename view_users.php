<?php
// view_users.php
$host = 'localhost';
$dbname = 'online_market';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "✅ Database connected successfully!<br><br>";
    
    // Show all users
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "📋 Existing Users:<br>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'>";
        foreach (array_keys($users[0]) as $column) {
            echo "<th>{$column}</th>";
        }
        echo "</tr>";
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No users in the table.";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>