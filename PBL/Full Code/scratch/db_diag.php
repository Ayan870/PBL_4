<?php
require_once __DIR__ . "/../config/db.php";

echo "--- DEPARTMENTS ---\n";
$res = mysqli_query($conn, "SELECT * FROM departments");
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));

echo "\n--- PROGRAMS ---\n";
$res = mysqli_query($conn, "SELECT id, name, department_id FROM programs");
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));

echo "\n--- SUPERVISORS ---\n";
$res = mysqli_query($conn, "SELECT id, name, department_id FROM users WHERE role='supervisor'");
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));
