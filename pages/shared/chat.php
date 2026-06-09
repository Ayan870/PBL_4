<!-- Real-time Chat UI Component -->
<div id="chat-widget" class="chat-widget-container">
    <!-- Chat Toggle Button -->
    <button id="chat-toggle-btn" class="btn btn-primary rounded-circle shadow" onclick="if(window.togglePBLChat) window.togglePBLChat(); else console.error('Chat system not ready');">
        <i class="fas fa-comments"></i>
        <span id="total-unread-badge" class="badge bg-danger rounded-pill d-none">0</span>
    </button>

    <!-- Chat Container -->
    <div id="chat-box" class="card shadow d-none">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0"><i class="fas fa-comment-dots me-2"></i>Messages</h6>
            <button type="button" class="btn-close btn-close-white" onclick="window.togglePBLChat()"></button>
        </div>
        
        <div class="chat-body d-flex">
            <!-- Contact List -->
            <div id="chat-contacts" class="border-end bg-dark text-white">
                <div class="p-2 border-bottom border-secondary">
                    <input type="text" id="contact-search" class="form-control form-control-sm bg-dark border-secondary text-white" placeholder="Search..." autocomplete="off">
                </div>
                <div class="list-group list-group-flush" id="contacts-list">
                    <!-- Contacts will be loaded here -->
                </div>
            </div>

            <!-- Message Area -->
            <div id="chat-messages-container" class="d-none bg-dark">
                <div id="active-contact-header" class="p-2 border-bottom d-flex align-items-center bg-dark border-secondary">
                    <button class="btn btn-sm btn-link d-md-none me-2 text-white" id="back-to-contacts">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="position-relative me-2">
                        <div id="active-avatar" class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white bg-primary" style="width:35px; height:35px; font-size: 14px;">?</div>
                        <span id="active-status-dot" class="status-dot offline"></span>
                    </div>
                    <div>
                        <div class="fw-bold small text-white" id="active-contact-name">Loading...</div>
                        <div class="text-info smaller" id="typing-indicator" style="display:none">typing...</div>
                    </div>
                </div>
                
                <div id="messages-log" class="p-3 bg-dark">
                    <!-- Messages will appear here -->
                </div>

                <div class="p-2 border-top border-secondary">
                    <form id="chat-form" class="d-flex" autocomplete="off">
                        <input type="text" id="message-input" class="form-control form-control-sm me-2 bg-dark border-secondary text-white" placeholder="Type a message..." autocomplete="off">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-widget-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1050;
}

#chat-toggle-btn {
    width: 60px;
    height: 60px;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

#chat-box {
    position: fixed;
    bottom: 95px;
    right: 25px;
    width: 400px; /* Reduced width for better UX */
    height: 550px;
    display: flex;
    flex-direction: column;
    border: 1px solid #334155;
    background: #1e293b;
    z-index: 1051;
}

@media (max-width: 768px) {
    #chat-box {
        width: calc(100vw - 40px);
        height: 80vh;
        bottom: 70px;
    }
}

.chat-body {
    flex-grow: 1;
    overflow: hidden;
    background: #1e293b;
}

#chat-contacts {
    width: 240px;
    height: 100%;
    overflow-y: auto;
    background: #0f172a !important;
}

#chat-messages-container {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #1e293b !important;
}

#messages-log {
    flex-grow: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    background: #111827 !important;
}

.message {
    max-width: 80%;
    margin-bottom: 12px;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 14px;
    position: relative;
    line-height: 1.4;
}

.message.sent {
    align-self: flex-end;
    background-color: #6366f1;
    color: white;
    border-bottom-right-radius: 2px;
}

.message.received {
    align-self: flex-start;
    background-color: #374151;
    color: #f3f4f6;
    border-bottom-left-radius: 2px;
}

.message-time {
    font-size: 10px;
    margin-top: 4px;
    opacity: 0.7;
    display: block;
}

.avatar-initials {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
    flex-shrink: 0;
}

.status-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid #0f172a;
}

.status-dot.online { background-color: #10b981; }
.status-dot.offline { background-color: #6b7280; }

.smaller { font-size: 11px; }

#contacts-list .list-group-item {
    cursor: pointer;
    background: transparent;
    border-color: rgba(255,255,255,0.05);
    color: #94a3b8;
    transition: all 0.2s;
}

#contacts-list .list-group-item:hover {
    background-color: rgba(255,255,255,0.05);
    color: white;
}

#contacts-list .list-group-item.active {
    background-color: rgba(99, 102, 241, 0.15);
    color: #818cf8;
    border-left: 3px solid #6366f1;
}

#total-unread-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 11px;
    padding: 4px 7px;
    border: 2px solid #0f172a;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

.unread-badge {
    font-size: 10px;
    padding: 3px 6px;
    min-width: 18px;
    text-align: center;
}

.bg-dark { background-color: #0f172a !important; }
.border-secondary { border-color: #334155 !important; }
</style>
