<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "
    SELECT gm.*, u.roll_number, u.name 
    FROM group_members gm 
    JOIN users u ON gm.student_id = u.id
");
echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC), JSON_PRETTY_PRINT);
