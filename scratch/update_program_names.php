<?php
require_once "config/db.php";

$update_query = "UPDATE programs SET name = REPLACE(name, 'Morning', 'M') WHERE name LIKE '%Morning%'";
if (mysqli_query($conn, $update_query)) {
    $affected = mysqli_affected_rows($conn);
    echo "Updated $affected program names. 'Morning' replaced with 'M'.\n";
} else {
    echo "Error updating programs: " . mysqli_error($conn) . "\n";
}
?>
