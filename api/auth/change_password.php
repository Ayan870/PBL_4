<?php
session_start();
header("Content-Type: application/json");

if (empty($_SESSION["user_id"])) {
  echo json_encode(["success" => false, "message" => "Not logged in."]);
  exit;
}

require_once "../../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  echo json_encode(["success" => false, "message" => "Invalid request."]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
  echo json_encode(["success" => false, "message" => "No data received."]);
  exit;
}

$current = (string)($data["current_password"] ?? "");
$next    = (string)($data["new_password"] ?? "");

if (strlen($next) < 6) {
  echo json_encode(["success" => false, "message" => "New password must be at least 6 characters."]);
  exit;
}

$userId = (int)$_SESSION["user_id"];

$stmt = mysqli_prepare($conn, "SELECT password_hash FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
  echo json_encode(["success" => false, "message" => "User not found."]);
  exit;
}

$currentHash = hash("sha256", $current);
if (!hash_equals($row["password_hash"], $currentHash)) {
  echo json_encode(["success" => false, "message" => "Current password is incorrect."]);
  exit;
}

$nextHash = hash("sha256", $next);
$upd = mysqli_prepare($conn, "UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
mysqli_stmt_bind_param($upd, "si", $nextHash, $userId);

if (!mysqli_stmt_execute($upd)) {
  echo json_encode(["success" => false, "message" => "Failed to update password."]);
  exit;
}

echo json_encode(["success" => true, "message" => "Password updated successfully."]);
mysqli_close($conn);

