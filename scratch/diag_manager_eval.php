<?php
session_start();
require_once __DIR__ . "/../config/db.php";

$dept_id = $_SESSION["user_dept_id"] ?? 0;
echo "DEPT ID: $dept_id\n";

$prog_res = mysqli_query($conn, "SELECT id, name FROM programs WHERE department_id = $dept_id");
echo "PROGRAMS COUNT: " . mysqli_num_rows($prog_res) . "\n";
while($p = mysqli_fetch_assoc($prog_res)) {
    echo " - " . $p['name'] . "\n";
}

$sup_res = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'supervisor' AND department_id = $dept_id");
echo "SUPERVISORS COUNT: " . mysqli_num_rows($sup_res) . "\n";
while($s = mysqli_fetch_assoc($sup_res)) {
    echo " - " . $s['name'] . "\n";
}
