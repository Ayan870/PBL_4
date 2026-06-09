<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$tables = ['class_supervisors', 'groups', 'proposals', 'proposal_attachments', 'group_members', 'users', 'classes', 'programs', 'departments'];
$schema = [];

foreach ($tables as $table) {
    $res = mysqli_query($conn, "DESCRIBE `$table` ");
    if ($res) {
        $cols = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $cols[] = $row['Field'];
        }
        $schema[$table] = $cols;
    } else {
        $schema[$table] = "ERROR: Table not found or " . mysqli_error($conn);
    }
}

// Check some data counts
$counts = [];
foreach ($tables as $table) {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `$table` ");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $counts[$table] = $row['cnt'];
    }
}

echo json_encode([
    "schema" => $schema,
    "counts" => $counts,
    "session" => isset($_SESSION) ? $_SESSION : "No session started"
], JSON_PRETTY_PRINT);
?>
