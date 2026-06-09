<?php
require_once __DIR__ . "/../config/db.php";
$roll = 'SU74-BSCSM-F24-001';
$res = mysqli_query($conn, "SELECT id FROM users WHERE roll_number = '$roll'");
$user = mysqli_fetch_assoc($res);
if (!$user) die("Student $roll not found.\n");
$user_id = $user['id'];

// Simulate create_group.php
$_SESSION['user_id'] = $user_id;
$_SESSION['user_role'] = 'student';

// Include the logic or just run SQL
// We need class_id and subject_id for BSCSM
$prog_res = mysqli_query($conn, "SELECT id FROM programs WHERE code = 'BSCSM' LIMIT 1");
$prog = mysqli_fetch_assoc($prog_res);
$prog_id = $prog['id'];

$class_res = mysqli_query($conn, "SELECT id FROM classes WHERE program_id = $prog_id LIMIT 1");
$class = mysqli_fetch_assoc($class_res);
$class_id = $class['id'];

$sub_res = mysqli_query($conn, "SELECT id FROM pbl_subjects WHERE program_id = $prog_id LIMIT 1");
$sub = mysqli_fetch_assoc($sub_res);
$sub_id = $sub['id'];

$ins_g = "INSERT INTO `groups` (class_id, pbl_subject_id, name, created_by, status) VALUES ($class_id, $sub_id, 'Test Group', $user_id, 'forming')";
mysqli_query($conn, $ins_g);
$group_id = mysqli_insert_id($conn);

$ins_m = "INSERT INTO group_members (group_id, student_id, role, invite_status, joined_at) VALUES ($group_id, $user_id, 'leader', 'accepted', CURRENT_TIMESTAMP)";
mysqli_query($conn, $ins_m);

echo "Group created for $roll (ID $group_id)\n";
