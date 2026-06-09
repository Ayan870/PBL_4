<?php
require_once __DIR__ . "/../config/db.php";

$sql = "ALTER TABLE flex_submissions ADD COLUMN status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending'";

if (mysqli_query($conn, $sql)) {
    echo "Column status added to flex_submissions successfully\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
?>
