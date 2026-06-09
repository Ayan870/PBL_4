/**
 * Real-time Chat System
 * Client-side Logic
 */

class PBLChat {
    constructor(userId, userRole) {
        try {
            this.userId = userId;
            this.userRole = userRole;
            this.socket = null;
            this.activeReceiverId = null;
            this.activeConversationId = null;
            this.serverUrl = `ws://${window.location.hostname}:8765/ws/${userId}/${userRole}`;
            this.apiUrl = `http://${window.location.hostname}:8765`;
            
            console.log("Initializing PBLChat for user:", userId, userRole);
            this.initElements();
            this.initEvents();
            this.connect();
            this.loadContacts();
            
            // Attach this instance to the global toggle
            window._pblChatInstance = this;
        } catch (e) {
            console.error("Critical error in PBLChat constructor:", e);
        }
    }

    initElements() {
        this.toggleBtn = document.getElementById('chat-toggle-btn');
        this.chatBox = document.getElementById('chat-box');
        this.closeBtn = document.getElementById('close-chat');
        this.contactsList = document.getElementById('contacts-list');
        this.messagesLog = document.getElementById('messages-log');
        this.messageInput = document.getElementById('message-input');
        this.chatForm = document.getElementById('chat-form');
        this.messageContainer = document.getElementById('chat-messages-container');
        this.activeName = document.getElementById('active-contact-name');
        this.typingIndicator = document.getElementById('typing-indicator');
        this.activeStatusDot = document.getElementById('active-status-dot');
        this.contactSearch = document.getElementById('contact-search');
        this.totalUnreadBadge = document.getElementById('total-unread-badge');
        this.allContacts = []; // Store contacts for searching
        this.unreadCounts = {}; // contactId -> count
    }

    initEvents() {
        console.log("Registering chat events");
        if (this.toggleBtn) {
            this.toggleBtn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.togglePBLChat();
            };
        } else {
            console.error("Chat toggle button not found in DOM!");
        }

        if (this.closeBtn) {
            this.closeBtn.onclick = () => this.chatBox.classList.add('d-none');
        }
        console.log("Registering chat events");
        if (this.toggleBtn) {
            this.toggleBtn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.togglePBLChat();
            };
        } else {
            console.error("Chat toggle button not found in DOM!");
        }

        if (this.closeBtn) {
            this.closeBtn.onclick = () => this.chatBox.classList.add('d-none');
        }
        
        this.chatForm.onsubmit = (e) => {
            e.preventDefault();
            this.sendMessage();
        };

        this.messageInput.oninput = () => {
            this.sendTypingStatus(true);
            clearTimeout(this.typingTimeout);
            this.typingTimeout = setTimeout(() => this.sendTypingStatus(false), 2000);
        };

        // Implementation of search
        this.contactSearch.oninput = () => {
            const term = this.contactSearch.value.toLowerCase();
            const filtered = this.allContacts.filter(c => 
                c.name.toLowerCase().includes(term) || 
                c.role.toLowerCase().includes(term)
            );
            this.renderContacts(filtered);
        };
    }

    connect() {
        this.socket = new WebSocket(this.serverUrl);

        this.socket.onopen = () => {
            console.log("Connected to Chat Server");
        };

        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleSocketMessage(data);
        };

        this.socket.onclose = () => {
            console.log("Disconnected from Chat Server. Reconnecting...");
            setTimeout(() => this.connect(), 3000);
        };

        this.socket.onerror = (error) => {
            console.error("WebSocket Error: ", error);
        };
    }

    async loadContacts() {
        console.log("Loading contacts for user ID:", this.userId);
        try {
            const response = await fetch(`${this.apiUrl}/contacts/${this.userId}`);
            if (!response.ok) {
                throw new Error(`Server returned status ${response.status}`);
            }
            const contacts = await response.json();
            console.log("Raw contacts received:", contacts);

            if (!Array.isArray(contacts)) {
                console.error("Expected array of contacts, got:", contacts);
                this.contactsList.innerHTML = '<div class="p-3 text-center text-danger smaller">Error: Invalid server response</div>';
                return;
            }

            // Initialize last_message_time if not present
            this.allContacts = contacts.map(c => ({
                ...c,
                last_message_time: c.last_message_time || '1970-01-01T00:00:00'
            }));
            
            console.log("Processed contacts:", this.allContacts.length);
            this.sortAndRenderContacts();
        } catch (error) {
            console.error("Failed to load contacts:", error);
            this.contactsList.innerHTML = `<div class="p-3 text-center text-danger smaller">Connection failed: ${error.message}</div>`;
        }
    }

    sortAndRenderContacts() {
        // Sort by last message time (descending)
        this.allContacts.sort((a, b) => new Date(b.last_message_time) - new Date(a.last_message_time));
        this.renderContacts(this.allContacts);
    }

    getInitials(name) {
        if (!name) return "?";
        return name.split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }

    getAvatarColor(name) {
        const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#06b6d4'];
        let hash = 0;
        for (let i = 0; i < name.length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }
        return colors[Math.abs(hash) % colors.length];
    }

    renderContacts(contacts) {
        this.contactsList.innerHTML = '';
        if (contacts.length === 0) {
            this.contactsList.innerHTML = '<div class="p-3 text-center text-muted smaller">No contacts found</div>';
            return;
        }

        contacts.forEach(contact => {
            const initials = this.getInitials(contact.name);
            const color = this.getAvatarColor(contact.name);
            const unread = this.unreadCounts[contact.id] || 0;
            
            const item = document.createElement('div');
            item.className = `list-group-item d-flex align-items-center p-3 ${this.activeReceiverId == contact.id ? 'active' : ''}`;
            item.dataset.id = contact.id;
            item.innerHTML = `
                <div class="position-relative me-3">
                    <div class="avatar-initials" style="background-color: ${color}">${initials}</div>
                    <span class="status-dot ${contact.is_online ? 'online' : 'offline'}" id="status-${contact.id}"></span>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold smaller text-white text-truncate">${contact.name}</span>
                        ${unread > 0 ? `<span class="badge bg-danger rounded-pill unread-badge">${unread}</span>` : ''}
                    </div>
                    <div class="smaller text-muted text-truncate">${contact.role}</div>
                </div>
            `;
            item.onclick = () => this.selectContact(contact);
            this.contactsList.appendChild(item);
        });
    }

    async selectContact(contact) {
        this.activeReceiverId = contact.id;
        this.activeName.innerText = contact.name;
        this.activeStatusDot.className = `status-dot ${contact.is_online ? 'online' : 'offline'}`;
        this.messageContainer.classList.remove('d-none');
        
        // Clear unread for this contact
        if (this.unreadCounts[contact.id]) {
            delete this.unreadCounts[contact.id];
            this.updateTotalUnreadBadge();
            this.sortAndRenderContacts();
        }

        // Update header avatar
        const activeAvatar = document.getElementById('active-avatar');
        activeAvatar.innerText = this.getInitials(contact.name);
        activeAvatar.style.backgroundColor = this.getAvatarColor(contact.name);

        // Mark as active in UI
        document.querySelectorAll('#contacts-list .list-group-item').forEach(el => el.classList.remove('active'));
        document.querySelector(`#contacts-list .list-group-item[data-id="${contact.id}"]`)?.classList.add('active');

        // Load history
        this.messagesLog.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
        
        try {
            const response = await fetch(`${this.apiUrl}/history/${this.userId}/${contact.id}`);
            const messages = await response.json();
            
            this.messagesLog.innerHTML = '';
            if (messages.length === 0) {
                this.messagesLog.innerHTML = '<div class="text-center p-4 text-muted smaller">No previous messages. Start the conversation!</div>';
            } else {
                messages.forEach(msg => this.appendMessage(msg));
            }
            
            // Mark all fetched messages as seen if they were sent to us
            if (messages.length > 0) {
                const lastMsg = messages[messages.length - 1];
                if (lastMsg.sender_id != this.userId) {
                    this.socket.send(JSON.stringify({
                        type: "mark_seen",
                        message_id: lastMsg.id,
                        sender_id: lastMsg.sender_id
                    }));
                }
            }
        } catch (error) {
            console.error("Error loading history", error);
            this.messagesLog.innerHTML = '<div class="text-center p-3 text-danger">Error loading history</div>';
        }
    }

    handleSocketMessage(data) {
        console.log("Chat message received:", data); // Debug log
        
        switch (data.type) {
            case 'chat_message':
                const isFromMe = String(data.sender_id) === String(this.userId);
                const isForMe = String(data.receiver_id) === String(this.userId);
                const isFromActive = String(data.sender_id) === String(this.activeReceiverId);

                // Ignore if not for us and not from us
                if (!isFromMe && !isForMe) return;

                // Update last_message_time for sorting
                const contact = this.allContacts.find(c => String(c.id) === String(data.sender_id) || String(c.id) === String(data.receiver_id));
                if (contact) {
                    contact.last_message_time = data.timestamp || new Date().toISOString();
                }
                
                if (isFromActive || isFromMe) {
                    this.appendMessage(data);
                }
                
                // Handle unread notifications
                if (!isFromMe && !isFromActive) {
                    this.updateUnreadBadge(data.sender_id);
                    this.playNotificationSound();
                }

                // Re-sort to move the person to top
                this.sortAndRenderContacts();
                break;
            case 'typing':
                if (String(data.sender_id) === String(this.activeReceiverId)) {
                    this.typingIndicator.style.display = data.is_typing ? 'block' : 'none';
                }
                break;
            case 'status':
                const dot = document.getElementById(`status-${data.user_id}`);
                if (dot) dot.className = `status-dot ${data.status}`;
                if (data.user_id == this.activeReceiverId) {
                    this.activeStatusDot.className = `status-dot ${data.status}`;
                }
                // Update in memory list too
                const foundContact = this.allContacts.find(c => c.id == data.user_id);
                if (foundContact) foundContact.is_online = (data.status === 'online');
                break;
            case 'error':
                console.error(data.message);
                break;
        }
    }

    appendMessage(msg) {
        const div = document.createElement('div');
        const isSent = msg.sender_id == this.userId;
        div.className = `message ${isSent ? 'sent' : 'received'}`;
        
        const time = new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        div.innerHTML = `
            <div>${msg.message}</div>
            <span class="message-time">${time}</span>
        `;
        
        this.messagesLog.appendChild(div);
        this.messagesLog.scrollTop = this.messagesLog.scrollHeight;
    }

    sendMessage() {
        const text = this.messageInput.value.trim();
        if (!text || !this.activeReceiverId) return;

        const payload = {
            type: "chat_message",
            receiver_id: this.activeReceiverId,
            message: text
        };

        this.socket.send(JSON.stringify(payload));
        this.messageInput.value = '';
        this.sendTypingStatus(false);
    }

    sendTypingStatus(isTyping) {
        if (!this.activeReceiverId || !this.socket) return;
        this.socket.send(JSON.stringify({
            type: "typing",
            receiver_id: this.activeReceiverId,
            is_typing: isTyping
        }));
    }

    updateUnreadBadge(senderId) {
        this.unreadCounts[senderId] = (this.unreadCounts[senderId] || 0) + 1;
        this.updateTotalUnreadBadge();
    }

    updateTotalUnreadBadge() {
        const total = Object.values(this.unreadCounts).reduce((a, b) => a + b, 0);
        if (total > 0) {
            this.totalUnreadBadge.innerText = total;
            this.totalUnreadBadge.classList.remove('d-none');
        } else {
            this.totalUnreadBadge.classList.add('d-none');
        }
    }

    playNotificationSound() {
        // Optional: Add a small sound effect
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3');
        audio.play().catch(() => {}); // Catch browser block
    }

    selectContactById(id) {
        // Open the chat box if closed
        this.chatBox.classList.remove('d-none');
        
        // Find the contact in allContacts
        const contact = this.allContacts.find(c => String(c.id) === String(id));
        if (contact) {
            this.selectContact(contact);
        } else {
            // If contacts haven't loaded yet, try again in a bit
            setTimeout(() => {
                const retryContact = this.allContacts.find(c => String(c.id) === String(id));
                if (retryContact) this.selectContact(retryContact);
            }, 500);
        }
    }
}
