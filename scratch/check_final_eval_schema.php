<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "DESCRIBE final_evaluations");
echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC), JSON_PRETTY_PRINT);
