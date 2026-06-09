<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
header("Content-Type: application/json");

try {
    require_once "../../config/db.php";

    if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    $user_id = $_SESSION["user_id"];

    // 1. Get student's group info and role
    $query_group = "
        SELECT g.id as group_id, g.class_id, g.pbl_subject_id, gm.role
        FROM group_members gm
        JOIN `groups` g ON gm.group_id = g.id
        WHERE gm.student_id = ? AND gm.invite_status = 'accepted'
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $query_group);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $group_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$group_data) {
        throw new Exception("You are not part of any accepted group.");
    }

    if ($group_data["role"] !== 'leader') {
        throw new Exception("Only the group leader can submit the flex document.");
    }

    $group_id = $group_data["group_id"];
    $class_id = $group_data["class_id"];
    $pbl_subject_id = $group_data["pbl_subject_id"];

    // 2. Get assigned supervisor
    $query_sup = "
        SELECT supervisor_id 
        FROM class_supervisors 
        WHERE class_id = ? AND pbl_subject_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $query_sup);
    mysqli_stmt_bind_param($stmt, "ii", $class_id, $pbl_subject_id);
    mysqli_stmt_execute($stmt);
    $sup_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$sup_data) {
        throw new Exception("No supervisor assigned to your class for this subject yet.");
    }

    $supervisor_id = $sup_data["supervisor_id"];

    // 3. Handle File Upload
    if (!isset($_FILES["flex_file"]) || $_FILES["flex_file"]["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Please upload a valid PDF or PNG file.");
    }

    $upload_dir = "../../uploads/flex/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_original_name = basename($_FILES["flex_file"]["name"]);
    $ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
    
    if (!in_array($ext, ['pdf', 'png'])) {
        throw new Exception("Only PDF and PNG files are allowed.");
    }

    $file_name = time() . "_flex_" . $file_original_name;
    $file_path = "uploads/flex/" . $file_name;

    if (!move_uploaded_file($_FILES["flex_file"]["tmp_name"], $upload_dir . $file_name)) {
        throw new Exception("Failed to upload file.");
    }

    // 4. Insert into flex_submissions (Update if exists, or just insert new)
    // The user said "one for flex we submit at end of semester"
    // We can allow re-submission by checking if a record exists
    $check_query = "SELECT id FROM flex_submissions WHERE group_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $group_id);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($existing) {
        $sql = "UPDATE flex_submissions SET supervisor_id = ?, file_path = ?, file_name = ?, created_at = CURRENT_TIMESTAMP WHERE group_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issi", $supervisor_id, $file_path, $file_original_name, $group_id);
    } else {
        $sql = "INSERT INTO flex_submissions (group_id, supervisor_id, file_path, file_name) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiss", $group_id, $supervisor_id, $file_path, $file_original_name);
    }

    if (mysqli_stmt_execute($stmt)) {
        // Send notification to supervisor
        $notif_msg = "Group '{$group_data['group_name']}' has submitted their Flex document.";
        $notif_link = "review-proposals.php"; // Supervisor can see it there
        $notif_sql = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
        $notif_stmt = mysqli_prepare($conn, $notif_sql);
        mysqli_stmt_bind_param($notif_stmt, "iss", $supervisor_id, $notif_msg, $notif_link);
        mysqli_stmt_execute($notif_stmt);

        echo json_encode(["success" => true, "message" => "Flex document submitted successfully!"]);
    } else {
        throw new Exception("Database error: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

mysqli_close($conn);
?>
