<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "SELECT g.id, g.name, u.name as leader FROM `groups` g JOIN users u ON g.created_by = u.id");
$groups = [];
while($row = mysqli_fetch_assoc($res)) {
    $groups[] = $row;
}
header('Content-Type: application/json');
echo json_encode($groups, JSON_PRETTY_PRINT);
