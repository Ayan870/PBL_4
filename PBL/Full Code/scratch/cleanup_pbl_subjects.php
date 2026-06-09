<?php
require_once "../config/db.php";

echo "<h2>Database Cleanup Utility</h2>";

// 1. Get all valid subject titles from master list
$master_titles = [];
$master_res = mysqli_query($conn, "SELECT title FROM subjects_master");
if ($master_res) {
    while ($row = mysqli_fetch_assoc($master_res)) {
        $master_titles[] = mysqli_real_escape_string($conn, $row['title']);
    }
}

if (empty($master_titles)) {
    die("Error: No subjects found in subjects_master. Please run init_subjects.php first.");
}

$valid_titles_str = "'" . implode("','", $master_titles) . "'";

echo "Valid Subjects: " . implode(", ", $master_titles) . "<br><br>";

// 2. Identify invalid subjects in pbl_subjects
$invalid_res = mysqli_query($conn, "SELECT id, title FROM pbl_subjects WHERE title NOT IN ($valid_titles_str)");
$invalid_ids = [];
while ($row = mysqli_fetch_assoc($invalid_res)) {
    $invalid_ids[] = $row['id'];
    echo "Found invalid subject: <b>" . $row['title'] . "</b> (ID: " . $row['id'] . ")<br>";
}

if (!empty($invalid_ids)) {
    $ids_str = implode(",", $invalid_ids);
    
    // 3. Delete from class_supervisors first (foreign key constraint)
    mysqli_query($conn, "DELETE FROM class_supervisors WHERE pbl_subject_id IN ($ids_str)");
    echo "Removed linked supervisor assignments.<br>";
    
    // 4. Delete from pbl_subjects
    mysqli_query($conn, "DELETE FROM pbl_subjects WHERE id IN ($ids_str)");
    echo "Successfully deleted " . count($invalid_ids) . " invalid subjects.<br>";
} else {
    echo "No invalid subjects found.<br>";
}

echo "<br><b>Done!</b> Your database is now clean. Please refresh your assignment page.";
?>
