<?php
require_once "../../config/db.php";
header("Content-Type: application/json");

try {
    // 1. Check if column exists
    $check = mysqli_query($conn, "SHOW COLUMNS FROM proposals LIKE 'supervisor_id'");
    if (mysqli_num_rows($check) == 0) {
        // 2. Add column
        $q1 = "ALTER TABLE proposals ADD COLUMN supervisor_id INT UNSIGNED NULL AFTER group_id";
        if (!mysqli_query($conn, $q1)) throw new Exception("Failed to add column: " . mysqli_error($conn));
        
        // 3. Add foreign key
        $q2 = "ALTER TABLE proposals ADD CONSTRAINT fk_proposals_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE";
        @mysqli_query($conn, $q2); // Ignore if FK fails
        
        echo json_encode(["success" => true, "message" => "Column 'supervisor_id' added successfully!"]);
    } else {
        echo json_encode(["success" => true, "message" => "Column 'supervisor_id' already exists."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
