<?php
require_once "../../config/db.php";
header("Content-Type: application/json");

function add_column($conn, $table, $col, $def) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    if (mysqli_num_rows($res) == 0) {
        $q = "ALTER TABLE `$table` ADD COLUMN `$col` $def";
        return mysqli_query($conn, $q);
    }
    return true;
}

try {
    $results = [];
    $results['supervisor_id'] = add_column($conn, 'proposals', 'supervisor_id', 'INT UNSIGNED NULL AFTER group_id');
    $results['category'] = add_column($conn, 'proposals', 'category', 'VARCHAR(100) NULL AFTER title');
    $results['objectives'] = add_column($conn, 'proposals', 'objectives', 'TEXT NULL');
    $results['methodology'] = add_column($conn, 'proposals', 'methodology', 'TEXT NULL');
    
    echo json_encode(["success" => true, "results" => $results]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
