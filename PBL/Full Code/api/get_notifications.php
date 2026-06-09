<?php
/**
 * Notifications API - PROJECXIA
 */

require_once __DIR__ . "/../helpers/api_helper.php";
apiRequireAuth();

$user_id = $_SESSION["user_id"];

try {
    // Mark as read if ID provided
    if (isset($_GET['mark_read'])) {
        $notif_id = (int)$_GET['mark_read'];
        $update_sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ii", $notif_id, $user_id);
        mysqli_stmt_execute($stmt);
        sendResponse(true, "Marked as read");
    }

    // Get unread notifications
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }

    sendResponse(true, "Notifications fetched", ["notifications" => $notifications]);

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}

mysqli_close($conn);
?>
