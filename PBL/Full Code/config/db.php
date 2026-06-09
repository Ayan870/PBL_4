<?php
// =============================================
// Database Connection - PROJECXIA
// =============================================

mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "projecxia";

// Try default port (3306)
$conn = @mysqli_connect($host, $user, $pass, $db);

// If failed, try common XAMPP alternative port (3307)
if (!$conn) {
    $conn = @mysqli_connect($host, $user, $pass, $db, 3307);
}

if (!$conn) {
    header("Content-Type: application/json");
    $err = mysqli_connect_error();
    $msg = "Database connection failed. ";
    
    if (strpos($err, "Access denied") !== false) {
        $msg .= "Access Denied. If you set a password for MySQL, please update it in config/db.php.";
    } elseif (strpos($err, "Unknown database") !== false) {
        $msg .= "Database 'projecxia' not found. Please create it in phpMyAdmin and rename your database.";
    } else {
        $msg .= "Error: " . $err;
    }

    echo json_encode([
        "success" => false,
        "message" => $msg
    ]);
    exit;
}

function pbl_has_column($conn, $table, $column) {
    static $cache = [];
    $key = strtolower($table . "." . $column);
    if (array_key_exists($key, $cache)) return $cache[$key];

    $t = mysqli_real_escape_string($conn, $table);
    $c = mysqli_real_escape_string($conn, $column);
    $sql = "SHOW COLUMNS FROM `$t` LIKE '$c'";
    $res = @mysqli_query($conn, $sql);
    $cache[$key] = $res && mysqli_num_rows($res) > 0;
    return $cache[$key];
}
?>
