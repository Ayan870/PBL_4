<?php
/**
 * Reset System API - PROVIA (Chairman only)
 * Resets all proposal, group, and evaluation data while preserving users and infrastructure.
 */

require_once __DIR__ . "/../../helpers/api_helper.php";
apiRequireRole('chairman');

try {
    // 1. Get existing tables in the database
    $existing_tables = [];
    $table_res = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_array($table_res)) {
        $existing_tables[] = $row[0];
    }

    // 2. Get all proposal attachment paths to delete files from disk
    $files_to_delete = [];
    if (in_array('proposal_attachments', $existing_tables)) {
        $query = "SELECT file_path FROM proposal_attachments";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $files_to_delete[] = __DIR__ . "/../../" . $row['file_path'];
            }
        }
    }

    // 3. Disable foreign key checks for mass truncation/deletion
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

    // 4. List of tables to clear (only if they exist)
    $tables_to_clear = [
        'proposal_attachments',
        'proposals',
        'evaluations',
        'mid_evaluations',
        'final_evaluations',
        'final_eval_sessions',
        'group_members',
        'groups',
        'class_supervisors',
        'pbl_subjects',
        'notifications',
        'messages',
        'conversation_participants',
        'conversations',
        'online_users'
    ];

    $cleared = [];
    foreach ($tables_to_clear as $table) {
        if (in_array($table, $existing_tables)) {
            if (!mysqli_query($conn, "TRUNCATE TABLE `$table`")) {
                // If truncate fails, use DELETE
                mysqli_query($conn, "DELETE FROM `$table` WHERE 1");
                mysqli_query($conn, "ALTER TABLE `$table` AUTO_INCREMENT = 1");
            }
            $cleared[] = $table;
        }
    }

    // 5. Re-enable foreign key checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

    // 6. Delete files from disk
    foreach ($files_to_delete as $file) {
        if (file_exists($file) && is_file($file)) {
            @unlink($file);
        }
    }

    sendResponse(true, "System reset successful. Cleared " . count($cleared) . " tables and deleted attachment files.");

} catch (Exception $e) {
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    sendResponse(false, "Error resetting system: " . $e->getMessage());
}

mysqli_close($conn);
?>
