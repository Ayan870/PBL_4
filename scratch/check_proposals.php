<?php
require_once 'config/db.php';
$res = mysqli_query($conn, 'DESC proposals');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
