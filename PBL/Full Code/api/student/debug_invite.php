<?php
require_once "../../config/db.php";
session_start();

$user_id = $_SESSION["user_id"] ?? 0;
$res = mysqli_query($conn, "SELECT name, roll_number FROM users WHERE id = $user_id");
$me = mysqli_fetch_assoc($res);

echo "Current User: " . $me['name'] . " (" . $me['roll_number'] . ") ID: " . $user_id . "\n\n";

$roll = $_GET['roll'] ?? '';
if ($roll) {
    $res = mysqli_query($conn, "SELECT id, name, roll_number FROM users WHERE roll_number = '$roll'");
    while($row = mysqli_fetch_assoc($res)) {
        echo "Found: " . $row['name'] . " (" . $row['roll_number'] . ") ID: " . $row['id'] . "\n";
    }
}
?>
