<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "SELECT id, name, role, department_id FROM users WHERE role = 'pbl_manager'");
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));
