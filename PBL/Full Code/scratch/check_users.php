<?php
require_once "config/db.php";

$query = "SELECT name, email, role, roll_number, password_hash FROM users LIMIT 20";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<h3>Existing Users</h3>";
    echo "<table border='1'><tr><th>Name</th><th>Email/Roll</th><th>Role</th><th>Password Hash</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $login_id = ($row['role'] === 'student') ? $row['roll_number'] : $row['email'];
        echo "<tr>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $login_id . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['password_hash'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for common simple passwords
    $passwords = ["123456", "password", "admin123", "pbl123", "12345678"];
    echo "<h4>Common Password Hashes (SHA256):</h4><ul>";
    foreach ($passwords as $p) {
        echo "<li>$p: " . hash("sha256", $p) . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
