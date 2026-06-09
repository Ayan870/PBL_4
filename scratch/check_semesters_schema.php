<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "DESCRIBE semesters");
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));
