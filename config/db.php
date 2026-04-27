<?php
// =============================================
// Database Connection - PBL Management System
// =============================================

$host = "localhost";
$user = "root";
$pass = "";
$db   = "pbl_management";

$conn = mysqli_connect($host, $user, $pass, $db, 3307);

if (mysqli_connect_errno()) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . mysqli_connect_error()
    ]);
    exit;
}