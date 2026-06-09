<?php
require_once "config/db.php";
$res = mysqli_query($conn, "SELECT id, name, role, roll_number FROM users");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
