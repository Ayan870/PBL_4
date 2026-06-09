<?php
require_once "config/db.php";
$res = mysqli_query($conn, "SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT 10");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
