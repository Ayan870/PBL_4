<?php
require_once __DIR__ . "/../config/db.php";

echo "Starting database migration...\n";

// 1. Move everything from BSCS Evening (ID 2) to BSCS Morning (ID 1)
$oldId = 2;
$newId = 1;

$tablesToUpdate = [
    'users' => 'program_id',
    'classes' => 'program_id',
    'pbl_subjects' => 'program_id'
];

foreach ($tablesToUpdate as $table => $col) {
    $sql = "UPDATE `$table` SET `$col` = $newId WHERE `$col` = $oldId";
    if (mysqli_query($conn, $sql)) {
        echo "Updated $table: moved program_id $oldId to $newId\n";
    } else {
        echo "Error updating $table: " . mysqli_error($conn) . "\n";
    }
}

// 2. Delete BSCS Evening
$sql = "DELETE FROM programs WHERE id = $oldId";
if (mysqli_query($conn, $sql)) {
    echo "Deleted program 'BSCS Evening' (ID $oldId)\n";
} else {
    echo "Error deleting program: " . mysqli_error($conn) . "\n";
}

// 3. Update other programs
$updates = [
    3 => ['name' => 'BS IT Morning',  'code' => 'BSITM'],
    4 => ['name' => 'BS AI Morning',  'code' => 'BSAIM'],
    5 => ['name' => 'BS CBS Morning', 'code' => 'BSCBSM'],
    8 => ['name' => 'BBA Morning',    'code' => 'BBAM'],
    9 => ['name' => 'BBS Morning',    'code' => 'BSBBSM']
];

foreach ($updates as $id => $data) {
    $name = mysqli_real_escape_string($conn, $data['name']);
    $code = mysqli_real_escape_string($conn, $data['code']);
    $sql = "UPDATE programs SET name = '$name', code = '$code' WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo "Updated program ID $id: $name ($code)\n";
    } else {
        echo "Error updating program ID $id: " . mysqli_error($conn) . "\n";
    }
}

// 4. Update existing student roll numbers
// We need to fetch students from programs we just updated (or the merged one)
$programCodes = [
    1 => 'BSCSM',
    3 => 'BSITM',
    4 => 'BSAIM',
    5 => 'BSCBSM',
    8 => 'BBAM',
    9 => 'BSBBSM'
];

// Special case: also handle the old 'BSCSE' if any students had it
$allCodesToFix = ['BSCSM', 'BSCSE', 'BSIT', 'BSAI', 'BSCBS', 'BBA', 'BBS'];

$res = mysqli_query($conn, "SELECT id, roll_number, program_id FROM users WHERE role = 'student' AND roll_number IS NOT NULL");
while ($user = mysqli_fetch_assoc($res)) {
    $oldRoll = $user['roll_number'];
    $progId = $user['program_id'];
    
    if (!isset($programCodes[$progId])) continue;
    $newCode = $programCodes[$progId];
    
    // Roll number format: SU74-[CODE]-F24-001
    $parts = explode("-", $oldRoll);
    if (count($parts) >= 4) {
        $parts[1] = $newCode; // Change the code part
        $newRoll = implode("-", $parts);
        
        if ($newRoll !== $oldRoll) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET roll_number = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newRoll, $user['id']);
            if (mysqli_stmt_execute($stmt)) {
                echo "Updated Roll: $oldRoll -> $newRoll\n";
            } else {
                echo "Error updating roll for user ID " . $user['id'] . ": " . mysqli_error($conn) . "\n";
            }
        }
    }
}

echo "Migration complete.\n";
