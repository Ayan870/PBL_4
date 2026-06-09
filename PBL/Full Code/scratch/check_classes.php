<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "SELECT c.id, p.name as program_name, s.name as semester_name, c.section FROM classes c JOIN programs p ON c.program_id = p.id JOIN semesters s ON c.semester_id = s.id");
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));
