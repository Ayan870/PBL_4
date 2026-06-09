<?php
require_once "config/db.php";
$queries = [
    "ALTER TABLE proposals ADD COLUMN supervisor_id INT UNSIGNED NULL AFTER group_id",
    "ALTER TABLE proposals ADD CONSTRAINT fk_proposals_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE"
];
foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " on query: $q\n";
    }
}
?>
