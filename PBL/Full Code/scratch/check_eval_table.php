<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "SHOW TABLES LIKE 'evaluations'");
if (mysqli_num_rows($res) > 0) {
    echo "EXISTS\n";
    $res2 = mysqli_query($conn, "DESCRIBE evaluations");
    echo json_encode(mysqli_fetch_all($res2, MYSQLI_ASSOC), JSON_PRETTY_PRINT);
} else {
    echo "NOT_EXISTS";
}
