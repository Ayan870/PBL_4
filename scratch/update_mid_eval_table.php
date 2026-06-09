<?php
require_once __DIR__ . "/../config/db.php";
$sql = "ALTER TABLE mid_eval_sessions ADD COLUMN status ENUM('active', 'terminated') DEFAULT 'active'";
if (mysqli_query($conn, $sql)) {
    echo "SUCCESS: Added status column to mid_eval_sessions\n";
} else {
    echo "ERROR: " . mysqli_error($conn) . "\n";
}
