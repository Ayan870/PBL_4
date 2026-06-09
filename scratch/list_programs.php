<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "SELECT p.id, p.name, p.code, d.name as dept FROM programs p JOIN departments d ON p.department_id = d.id");
$programs = [];
while($row = mysqli_fetch_assoc($res)) {
    $programs[] = $row;
}
header('Content-Type: application/json');
echo json_encode($programs, JSON_PRETTY_PRINT);
