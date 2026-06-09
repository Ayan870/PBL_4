<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "SELECT * FROM `groups` WHERE id = 1");
echo json_encode(mysqli_fetch_assoc($res), JSON_PRETTY_PRINT);
