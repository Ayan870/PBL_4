<?php
require_once "config/db.php";

$email = "chairman@gmail.com";
$password = "123456";
$password_hash = hash("sha256", $password);
$name = "System Chairman";

// Check if exists
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND role = 'chairman' LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {
    // Update password just in case
    $upd = mysqli_prepare($conn, "UPDATE users SET password_hash = ?, name = ? WHERE id = ?");
    mysqli_stmt_bind_param($upd, "ssi", $password_hash, $name, $row['id']);
    mysqli_stmt_execute($upd);
    echo "Chairman account already exists. Updated password and name.\n";
} else {
    // Insert new
    $ins = mysqli_prepare($conn, "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'chairman')");
    mysqli_stmt_bind_param($ins, "sss", $name, $email, $password_hash);
    if (mysqli_stmt_execute($ins)) {
        echo "Chairman account created successfully!\n";
    } else {
        echo "Error creating Chairman account: " . mysqli_error($conn) . "\n";
    }
}
?>
