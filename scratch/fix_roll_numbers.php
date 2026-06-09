<?php
require_once __DIR__ . '/../config/db.php';

// Fetch all students
$q = "
    SELECT u.id, u.program_id, p.code as prog_code, s.session, s.year
    FROM users u
    JOIN programs p ON u.program_id = p.id
    LEFT JOIN semesters s ON u.semester_id = s.id
    WHERE u.role = 'student'
    ORDER BY u.id ASC
";
$res = mysqli_query($conn, $q);

$counters = [];

while ($student = mysqli_fetch_assoc($res)) {
    $prog_code = strtoupper($student['prog_code']);
    
    // Determine session part (e.g., Fall 2024 -> F24, Spring 2024 -> S24)
    $session_str = $student['session'] ?: 'Fall';
    $year = $student['year'] ?: 2024;
    $session_char = strtoupper(substr($session_str, 0, 1));
    $year_short = substr($year, -2);
    $session_part = $session_char . $year_short;
    
    $group_key = $prog_code . "-" . $session_part;
    
    if (!isset($counters[$group_key])) {
        $counters[$group_key] = 1;
    }
    
    $seq = str_pad($counters[$group_key], 3, "0", STR_PAD_LEFT);
    $counters[$group_key]++;
    
    $new_roll = "SU74-" . $prog_code . "-" . $session_part . "-" . $seq;
    $new_email = strtolower($new_roll) . "@university.edu";
    
    $update_stmt = mysqli_prepare($conn, "UPDATE users SET roll_number = ?, email = ? WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "ssi", $new_roll, $new_email, $student['id']);
    mysqli_stmt_execute($update_stmt);
}

echo "Roll numbers and emails updated successfully for all students!\n";
?>
