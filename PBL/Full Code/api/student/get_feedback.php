<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$student_id = $_SESSION["user_id"];

// Get student's group
$group_query = "SELECT group_id FROM group_members WHERE student_id = ? AND invite_status = 'accepted' LIMIT 1";
$stmt_g = mysqli_prepare($conn, $group_query);
mysqli_stmt_bind_param($stmt_g, "i", $student_id);
mysqli_stmt_execute($stmt_g);
$group_res = mysqli_stmt_get_result($stmt_g);
$group_info = mysqli_fetch_assoc($group_res);
$group_id = $group_info['group_id'] ?? 0;

$all_feedback = [];

// 1. Get Individual Feedback (Student-level)
$query_ind = "
    SELECT 
        e.id, 
        e.supervisor_id, 
        u.name as supervisor_name, 
        e.eval_type,
        e.tech_score,
        e.feedback,
        e.recommendations,
        e.created_at
    FROM evaluations e
    JOIN users u ON e.supervisor_id = u.id
    WHERE e.student_id = ?
    ORDER BY e.created_at DESC
";
$stmt_ind = mysqli_prepare($conn, $query_ind);
mysqli_stmt_bind_param($stmt_ind, "i", $student_id);
mysqli_stmt_execute($stmt_ind);
$res_ind = mysqli_stmt_get_result($stmt_ind);
while ($row = mysqli_fetch_assoc($res_ind)) {
    $all_feedback[] = $row;
}

// 2. Get Mid-Term Feedback (Group-level summary)
if ($group_id) {
    $query1 = "
        SELECT 
            e.id, 
            e.evaluated_by as supervisor_id, 
            u.name as supervisor_name, 
            'Mid-Term Progress' as eval_type,
            e.marks as tech_score,
            e.feedback,
            CONCAT('Progress: ', e.progress_percent, '%') as recommendations,
            e.created_at
        FROM mid_evaluations e
        JOIN users u ON e.evaluated_by = u.id
        WHERE e.group_id = ?
        ORDER BY e.created_at DESC
    ";
    $stmt1 = mysqli_prepare($conn, $query1);
    mysqli_stmt_bind_param($stmt1, "i", $group_id);
    mysqli_stmt_execute($stmt1);
    $res1 = mysqli_stmt_get_result($stmt1);
    while ($row = mysqli_fetch_assoc($res1)) {
        $all_feedback[] = $row;
    }
}

// 2. Get Final Evaluation Feedback
if ($group_id) {
    $query2 = "
        SELECT 
            fe.id,
            fe.evaluator_id as supervisor_id,
            u.name as supervisor_name,
            'Final Evaluation' as eval_type,
            fe.marks_out_of_20 as tech_score,
            fe.feedback,
            '' as recommendations,
            fe.created_at
        FROM final_evaluations fe
        JOIN users u ON fe.evaluator_id = u.id
        WHERE fe.group_id = ?
        ORDER BY fe.created_at DESC
    ";
    $stmt2 = mysqli_prepare($conn, $query2);
    mysqli_stmt_bind_param($stmt2, "i", $group_id);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    while ($row = mysqli_fetch_assoc($res2)) {
        $all_feedback[] = $row;
    }

    // 3. Get Proposal Feedback
    $query3 = "
        SELECT 
            p.id,
            p.reviewed_by as supervisor_id,
            u.name as supervisor_name,
            'Proposal Review' as eval_type,
            p.status as tech_score,
            p.rejection_reason as feedback,
            p.title as recommendations,
            p.reviewed_at as created_at
        FROM proposals p
        LEFT JOIN users u ON p.reviewed_by = u.id
        WHERE p.group_id = ? AND p.reviewed_at IS NOT NULL
        ORDER BY p.reviewed_at DESC
    ";
    $stmt3 = mysqli_prepare($conn, $query3);
    mysqli_stmt_bind_param($stmt3, "i", $group_id);
    mysqli_stmt_execute($stmt3);
    $res3 = mysqli_stmt_get_result($stmt3);
    while ($row = mysqli_fetch_assoc($res3)) {
        $row['eval_type'] = 'Proposal Review (' . ucfirst($row['tech_score']) . ')';
        $row['tech_score'] = $row['tech_score'] === 'accepted' ? 'PASS' : 'FAIL';
        $all_feedback[] = $row;
    }
}

// Sort by date descending
usort($all_feedback, function($a, $b) {
    $t1 = $a['created_at'] ? strtotime($a['created_at']) : 0;
    $t2 = $b['created_at'] ? strtotime($b['created_at']) : 0;
    return $t2 - $t1;
});

echo json_encode([
    "success" => true,
    "feedback" => $all_feedback
]);

mysqli_close($conn);
?>
