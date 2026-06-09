<?php
// chat_init.php
// This file handles the inclusion of the chat UI and initialization of the WebSocket client.
?>

<!-- Include Chat UI -->
<?php include_once __DIR__ . '/chat.php'; ?>

<!-- Font Awesome for Chat Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Chat Logic -->
<script>
// Define toggle function FIRST so it always works
window.togglePBLChat = () => {
    const chatBox = document.getElementById('chat-box');
    if (chatBox) {
        chatBox.classList.toggle('d-none');
        console.log("Chat box visibility toggled");
    } else {
        console.error("Chat box element not found in DOM!");
    }
};

<?php 
// Inline the logic
$chat_js_path = dirname(__DIR__, 2) . '/assets/js/chat.js';
if (file_exists($chat_js_path)) {
    echo file_get_contents($chat_js_path);
} else {
    echo "console.error('Chat logic file not found at: " . addslashes($chat_js_path) . "');";
}
?>

(function() {
    const userId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
    const userRole = <?php echo json_encode($_SESSION['user_role'] ?? null); ?>;
    
    console.log("Chat system starting for " + userRole + " (ID: " + userId + ")");

    if (userId && userRole && userRole !== 'evaluator') {
        if (typeof PBLChat !== 'undefined') {
            try {
                window.pblChat = new PBLChat(userId, userRole);
                console.log("PBLChat initialized successfully");
            } catch (e) {
                console.error("Failed to initialize PBLChat:", e);
            }
        } else {
            console.error("PBLChat class is not defined after inlining!");
        }
    } else {
        console.warn("Chat initialization skipped: Invalid session or role");
    }
})();
</script>
