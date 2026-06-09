<?php
/**
 * Submit Proposal API - PROJECXIA
 */

require_once "../../helpers/api_helper.php";
apiRequireRole("student");

$user_id = $_SESSION["user_id"];

try {
    // 1. Get student's group info and role
    $query_group = "
        SELECT g.id as group_id, g.class_id, g.pbl_subject_id, gm.role
        FROM group_members gm
        JOIN `groups` g ON gm.group_id = g.id
        WHERE gm.student_id = ? AND gm.invite_status = 'accepted'
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $query_group);
    if (!$stmt) throw new Exception("Prepare failed (group): " . mysqli_error($conn));
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $group_res = mysqli_stmt_get_result($stmt);
    $group_data = mysqli_fetch_assoc($group_res);

    if (!$group_data) {
        throw new Exception("You are not part of any accepted group.");
    }

    if ($group_data["role"] !== 'leader') {
        throw new Exception("Only the group leader can submit a proposal.");
    }

    $group_id       = $group_data["group_id"];
    $class_id       = $group_data["class_id"];
    $pbl_subject_id = $group_data["pbl_subject_id"];

    // 1b. Check if an approved proposal already exists for this group
    $query_done = "SELECT id FROM proposals WHERE group_id = ? AND status = 'accepted' LIMIT 1";
    $stmt_d = mysqli_prepare($conn, $query_done);
    mysqli_stmt_bind_param($stmt_d, "i", $group_id);
    mysqli_stmt_execute($stmt_d);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_d))) {
        throw new Exception("Your proposal has already been approved. You cannot submit more proposals.");
    }

    // 2. Get assigned supervisor
    $query_sup = "
        SELECT supervisor_id 
        FROM class_supervisors 
        WHERE class_id = ? AND pbl_subject_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $query_sup);
    if (!$stmt) throw new Exception("Prepare failed (supervisor): " . mysqli_error($conn));
    
    mysqli_stmt_bind_param($stmt, "ii", $class_id, $pbl_subject_id);
    mysqli_stmt_execute($stmt);
    $sup_res = mysqli_stmt_get_result($stmt);
    $sup_data = mysqli_fetch_assoc($sup_res);

    if (!$sup_data) {
        throw new Exception("No supervisor assigned to your class for this subject yet. Please contact the PBL Manager.");
    }

    $supervisor_id = $sup_data["supervisor_id"];

    // 3. Get POST data
    $title       = $_POST["title"]       ?? "";
    $category    = $_POST["category"]    ?? "";
    $description = $_POST["abstract"]    ?? "";
    $objectives  = $_POST["objectives"]  ?? "";
    $methodology = $_POST["methodology"] ?? "";
    $tools       = $_POST["tools"]       ?? "";

    if (empty($title) || empty($description)) {
        throw new Exception("Title and Description are required.");
    }

    // 4. Handle File Upload
    $file_path = "";
    $file_original_name = "";
    if (isset($_FILES["proposal_file"]) && $_FILES["proposal_file"]["error"] === UPLOAD_ERR_OK) {
        $upload_dir = "../../uploads/proposals/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_original_name = basename($_FILES["proposal_file"]["name"]);
        $file_name = time() . "_" . $file_original_name;
        $file_path = "uploads/proposals/" . $file_name;
        
        if (!move_uploaded_file($_FILES["proposal_file"]["tmp_name"], $upload_dir . $file_name)) {
            throw new Exception("Failed to upload file.");
        }
    }

    // 5. Insert Proposal
    $stmt_ver = mysqli_prepare($conn, "SELECT MAX(version_number) as max_v FROM proposals WHERE group_id = ?");
    mysqli_stmt_bind_param($stmt_ver, "i", $group_id);
    mysqli_stmt_execute($stmt_ver);
    $ver_res = mysqli_stmt_get_result($stmt_ver);
    $ver_data = mysqli_fetch_assoc($ver_res);
    $version = ($ver_data["max_v"] ?? 0) + 1;

    // Use pbl_has_column from db.php (included via api_helper)
    $has_cat = pbl_has_column($conn, 'proposals', 'category');
    $has_sup = pbl_has_column($conn, 'proposals', 'supervisor_id');

    $cols = ["group_id", "version_number", "title", "description", "objectives", "methodology", "tools", "status"];
    $vals = [$group_id, $version, $title, $description, $objectives, $methodology, $tools, 'pending'];
    $types = "iissssss";

    if ($has_cat) { $cols[] = "category"; $vals[] = $category; $types .= "s"; }
    if ($has_sup) { $cols[] = "supervisor_id"; $vals[] = $supervisor_id; $types .= "i"; }

    $sql = "INSERT INTO proposals (" . implode(", ", $cols) . ") VALUES (" . implode(", ", array_fill(0, count($cols), "?")) . ")";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception("Database error (Insert Prepare): " . mysqli_error($conn));
    
    mysqli_stmt_bind_param($stmt, $types, ...$vals);
    
    if (mysqli_stmt_execute($stmt)) {
        $proposal_id = mysqli_insert_id($conn);
        
        // Get group name for notification
        $gn_res = mysqli_query($conn, "SELECT name FROM `groups` WHERE id = $group_id");
        $gn_data = mysqli_fetch_assoc($gn_res);
        $group_name = $gn_data['name'] ?? 'A group';

        // Send notification to supervisor
        $notif_msg = "{$group_name} has submitted a new project proposal: '{$title}'.";
        $notif_link = "review-proposals.php";
        $notif_sql = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
        $notif_stmt = mysqli_prepare($conn, $notif_sql);
        mysqli_stmt_bind_param($notif_stmt, "iss", $supervisor_id, $notif_msg, $notif_link);
        mysqli_stmt_execute($notif_stmt);

        if ($file_path) {
            $query_att = "INSERT INTO proposal_attachments (proposal_id, file_name, file_path) VALUES (?, ?, ?)";
            $stmt_att = mysqli_prepare($conn, $query_att);
            mysqli_stmt_bind_param($stmt_att, "iss", $proposal_id, $file_original_name, $file_path);
            mysqli_stmt_execute($stmt_att);
        }
        
        sendResponse(true, "Proposal submitted successfully!");
    } else {
        throw new Exception("Database error (Execute): " . mysqli_error($conn));
    }

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}

mysqli_close($conn);
?>
