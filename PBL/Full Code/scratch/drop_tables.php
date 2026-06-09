<?php
require 'config/db.php';
$tables = ['message_attachments', 'chat_participants', 'chat_rooms'];
foreach ($tables as $table) {
    if (mysqli_query($conn, "DROP TABLE IF EXISTS `$table`")) {
        echo "Dropped table $table\n";
    } else {
        echo "Error dropping $table: " . mysqli_error($conn) . "\n";
    }
}
?>
