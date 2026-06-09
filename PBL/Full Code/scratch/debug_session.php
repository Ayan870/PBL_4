<?php
session_start();
require_once __DIR__ . "/../config/db.php";

$user_id = $_SESSION["user_id"] ?? null;
$user_role = $_SESSION["user_role"] ?? null;

echo "Session User ID: " . var_export($user_id, true) . "\n";
echo "Session User Role: " . var_export($user_role, true) . "\n";

if ($user_id) {
    $res = mysqli_query($conn, "SELECT name, roll_number, program_id, semester_id FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($res);
    echo "User Data: " . json_encode($user, JSON_PRETTY_PRINT) . "\n";

    // Check group_members
    $res = mysqli_query($conn, "SELECT * FROM group_members WHERE student_id = $user_id");
    echo "Group Memberships: " . json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC), JSON_PRETTY_PRINT) . "\n";
}
