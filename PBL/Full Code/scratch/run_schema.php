<?php
require_once 'config/db.php';

$sql = file_get_contents('scratch/chat_schema.sql');

if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    echo "Database updated successfully!\n";
} else {
    echo "Error updating database: " . mysqli_error($conn) . "\n";
}
?>
