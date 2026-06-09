<?php
require_once __DIR__ . "/../config/db.php";

// Set Saad Shahzad (ID 8) to Dept 1 (CS & IT)
$res = mysqli_query($conn, "UPDATE users SET department_id = 1 WHERE id = 8");
if ($res) {
    echo "SUCCESS: Supervisor Saad Shahzad assigned to Department 1.\n";
} else {
    echo "ERROR: " . mysqli_error($conn) . "\n";
}

// Also check if there are any classes and subjects for Department 1 programs
$prog_res = mysqli_query($conn, "SELECT id FROM programs WHERE department_id = 1");
while ($p = mysqli_fetch_assoc($prog_res)) {
    $pid = $p['id'];
    $c_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM classes WHERE program_id = $pid"));
    $s_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM pbl_subjects WHERE program_id = $pid"));
    echo "Program ID $pid: $c_count classes, $s_count subjects.\n";
}
