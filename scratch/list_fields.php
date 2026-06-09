<?php
require_once 'config/db.php';
$res = mysqli_query($conn, 'DESC proposals');
$fields = [];
while($row = mysqli_fetch_assoc($res)) {
    $fields[] = $row['Field'];
}
echo implode(", ", $fields);
?>
