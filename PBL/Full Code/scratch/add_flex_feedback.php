<?php
require_once __DIR__ . "/../config/db.php";

$sql = "ALTER TABLE flex_submissions ADD COLUMN feedback TEXT DEFAULT NULL";

if (mysqli_query($conn, $sql)) {
    echo "Column feedback added to flex_submissions successfully\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
?>
