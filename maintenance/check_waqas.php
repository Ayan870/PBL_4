<?php
require_once "config/db.php";
$res = mysqli_query($conn, "SELECT id, name, email, role FROM users WHERE email = 'waqas@gmail.com'");
if (mysqli_num_rows($res) > 0) {
    while($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    echo "User not found.\n";
}
?>
