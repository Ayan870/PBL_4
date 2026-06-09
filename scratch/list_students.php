<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "SELECT u.id, u.name, u.roll_number, p.name as program FROM users u LEFT JOIN programs p ON u.program_id = p.id WHERE u.role = 'student'");
$students = [];
while($row = mysqli_fetch_assoc($res)) {
    $students[] = $row;
}
header('Content-Type: application/json');
echo json_encode($students, JSON_PRETTY_PRINT);
