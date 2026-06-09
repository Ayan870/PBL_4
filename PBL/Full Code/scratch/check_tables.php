<?php
require_once "config/db.php";

$tables = ['subjects_master', 'pbl_subjects', 'classes', 'programs', 'semesters', 'users'];
foreach ($tables as $table) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($res) > 0) {
        echo "Table '$table' exists.<br>";
    } else {
        echo "Table '$table' DOES NOT exist.<br>";
    }
}
?>
