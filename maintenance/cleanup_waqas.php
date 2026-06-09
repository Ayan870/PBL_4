<?php
require_once "config/db.php";
$email = 'waqas@gmail.com';
mysqli_query($conn, "DELETE FROM users WHERE email = '$email' AND role = 'student'");
if (mysqli_affected_rows($conn) > 0) {
    echo "Deleted incorrect student account for $email.\n";
} else {
    echo "No incorrect student account found for $email.\n";
}
?>
