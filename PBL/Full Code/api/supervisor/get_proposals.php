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

    // We join through class_supervisors and metadata tables
    $query = "
        SELECT pr.*, 
               g.name as group_name, 
               u.name as leader_name,
               u.roll_number as leader_roll,
               p.name as program_name
        FROM proposals pr
        JOIN `groups` g ON pr.group_id = g.id
        JOIN group_members gm ON g.id = gm.group_id AND gm.role = 'leader'
        JOIN users u ON gm.student_id = u.id
        JOIN class_supervisors cs ON g.class_id = cs.class_id
        LEFT JOIN classes c ON g.class_id = c.id
        LEFT JOIN programs p ON c.program_id = p.id
        WHERE cs.supervisor_id = $supervisor_id
        ORDER BY pr.id DESC
    ";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }

    $proposals = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $proposal_id = $row["id"];
        $group_id    = $row["group_id"];

        // Fetch members
        $res_m = mysqli_query($conn, "
            SELECT u.name, u.roll_number, gm.role
            FROM group_members gm
            JOIN users u ON gm.student_id = u.id
            WHERE gm.group_id = $group_id AND gm.invite_status = 'accepted'
        ");
        
        $members = [];
        if ($res_m) {
            while ($m = mysqli_fetch_assoc($res_m)) {
                $members[] = $m;
            }
        }
        $row["members"] = $members;

        // Fetch attachments
        $res_a = mysqli_query($conn, "SELECT file_name, file_path FROM proposal_attachments WHERE proposal_id = $proposal_id");
        $attachments = [];
        if ($res_a) {
            while ($a = mysqli_fetch_assoc($res_a)) {
                $attachments[] = $a;
            }
        }
        $row["attachments"] = $attachments;

        $proposals[] = $row;
    }

    echo json_encode([
        "success"   => true,
        "proposals" => $proposals
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Critical Error: " . $e->getMessage()
    ]);
}

if (isset($conn)) mysqli_close($conn);
?>
