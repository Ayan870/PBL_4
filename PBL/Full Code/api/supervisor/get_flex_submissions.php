<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
header("Content-Type: application/json");

try {
    require_once "../../config/db.php";

    if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "supervisor") {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    $supervisor_id = $_SESSION["user_id"];

    // Get flex submissions for groups where this supervisor is assigned
    $query = "
        SELECT f.*, g.name as group_name, p.name as program_name, s.session, s.number as semester_number
        FROM flex_submissions f
        JOIN `groups` g ON f.group_id = g.id
        JOIN classes c ON g.class_id = c.id
        JOIN programs p ON c.program_id = p.id
        JOIN semesters s ON c.semester_id = s.id
        WHERE f.supervisor_id = ?
        ORDER BY f.created_at DESC
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $supervisor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $submissions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $submissions[] = $row;
    }

    echo json_encode(["success" => true, "submissions" => $submissions]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

mysqli_close($conn);
?>
