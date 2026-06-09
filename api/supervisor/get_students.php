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
    $dept_id = $_SESSION["user_dept_id"] ?? 0;

    // 1. Get classes assigned to this supervisor
    $classes_query = "SELECT class_id FROM class_supervisors WHERE supervisor_id = $supervisor_id";
    $res = mysqli_query($conn, $classes_query);
    if (!$res) throw new Exception("Failed to fetch class assignments: " . mysqli_error($conn));

    $class_ids = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $class_ids[] = $row['class_id'];
    }

    // Also check groups they supervise directly
    $proj_sup_query = "SELECT class_id FROM `groups` WHERE supervisor_id = $supervisor_id";
    $res_p = mysqli_query($conn, $proj_sup_query);
    if ($res_p) {
        while ($row = mysqli_fetch_assoc($res_p)) {
            $class_ids[] = $row['class_id'];
        }
    }

    $ps_filter = "";
    if (!empty($class_ids)) {
        $class_ids = array_unique($class_ids);
        $ids_str = implode(',', $class_ids);

        // Get unique program/semester pairs assigned to this supervisor
        $ps_query = "SELECT DISTINCT program_id, semester_id FROM classes WHERE id IN ($ids_str)";
        $res_ps = mysqli_query($conn, $ps_query);
        $ps_conds = [];
        while ($row = mysqli_fetch_assoc($res_ps)) {
            $ps_conds[] = "(u.program_id = " . $row['program_id'] . " AND u.semester_id = " . $row['semester_id'] . ")";
        }
        if (!empty($ps_conds)) {
            $ps_filter = implode(' OR ', $ps_conds);
        }
    }

    // 2. Build student query with department fallback
    if ($ps_filter) {
        $where_clause = "u.role = 'student' AND ($ps_filter)";
    } else {
        // Fallback: Show students of the same department if no specific classes assigned
        $where_clause = "u.role = 'student' AND u.department_id = $dept_id";
    }

    $query = "
        SELECT DISTINCT u.id, u.name, u.email, u.roll_number, p.name as program_name
        FROM users u
        JOIN programs p ON u.program_id = p.id
        WHERE $where_clause
        ORDER BY u.name ASC
    ";

    $res_s = mysqli_query($conn, $query);
    if (!$res_s) {
        throw new Exception("Failed to fetch students: " . mysqli_error($conn));
    }

    $students = [];
    while ($s = mysqli_fetch_assoc($res_s)) {
        $students[] = $s;
    }

    echo json_encode([
        "success" => true,
        "students" => $students
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Critical Error: " . $e->getMessage()
    ]);
}

if (isset($conn)) mysqli_close($conn);
?>
