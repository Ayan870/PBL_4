<?php
require_once 'config/db.php';
$res = mysqli_query($conn, 'DESC mid_evaluations');
$cols = [];
while($row = mysqli_fetch_assoc($res)) {
    $cols[] = $row;
}
echo json_encode($cols, JSON_PRETTY_PRINT);
?>
