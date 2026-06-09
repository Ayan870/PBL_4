<?php
require_once __DIR__ . "/../config/db.php";

$sql = "CREATE TABLE IF NOT EXISTS flex_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    supervisor_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table flex_submissions created successfully\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
