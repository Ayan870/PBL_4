<?php
require_once "config/db.php";
$email = 'waqas@gmail.com';
$res = mysqli_query($conn, "UPDATE users SET role = 'supervisor', roll_number = NULL WHERE email = '$email'");
if (mysqli_affected_rows($conn) > 0) {
    echo "Successfully converted $email to Supervisor.\n";
} else {
    echo "Failed to convert $email or user not found.\n";
}
?>
