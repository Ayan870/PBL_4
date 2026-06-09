<?php
require_once "../../config/db.php";
$res = mysqli_query($conn, "DESC proposals");
$cols = [];
while($row = mysqli_fetch_assoc($res)) {
    $cols[] = $row['Field'];
}
echo json_encode($cols);
?>
