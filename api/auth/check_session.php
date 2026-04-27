<?php
session_start();
header("Content-Type: application/json");

if (empty($_SESSION["user_id"])) {
    echo json_encode([
        "success"   => false,
        "logged_in" => false,
        "redirect"  => "/pbl-management-system/index.html"
    ]);
    exit;
}

echo json_encode([
    "success"      => true,
    "logged_in"    => true,
    "user_id"      => $_SESSION["user_id"],
    "name"         => $_SESSION["user_name"],
    "role"         => $_SESSION["user_role"],
    "roll"         => $_SESSION["user_roll"]         ?? "",
    "email"        => $_SESSION["user_email"]        ?? "",
    "program"      => $_SESSION["user_program"]      ?? "",
    "program_code" => $_SESSION["user_program_code"] ?? ""
]);
