<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "
    SELECT u.id, u.name, u.roll_number, gm.group_id, gm.invite_status 
    FROM users u 
    LEFT JOIN group_members gm ON u.id = gm.student_id 
    WHERE u.role = 'student'
");
echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC), JSON_PRETTY_PRINT);
