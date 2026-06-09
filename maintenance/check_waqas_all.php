<?php
require_once "config/db.php";
$email = 'waqas@gmail.com';
$res = mysqli_query($conn, "SELECT id, name, email, role FROM users WHERE email = '$email'");
if (mysqli_num_rows($res) > 0) {
    while($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    echo "No user found with email $email.\n";
}
?>
