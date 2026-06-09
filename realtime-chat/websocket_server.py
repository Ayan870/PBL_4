import asyncio
import json
from datetime import datetime
from fastapi import FastAPI, WebSocket, WebSocketDisconnect, HTTPException, Depends
from fastapi.middleware.cors import CORSMiddleware
from database import Database
from connection_manager import manager

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

async def get_user_details(user_id: int):
    query = "SELECT id, role, department_id, program_id FROM users WHERE id = %s"
    res = await Database.execute(query, (user_id,))
    return res[0] if res else None

async def validate_chat_permission(sender_id: int, receiver_id: int):
    sender = await get_user_details(sender_id)
    receiver = await get_user_details(receiver_id)

    if not sender or not receiver:
        return False

    s_role = sender['role']
    r_role = receiver['role']
    s_dept = sender['department_id']
    r_dept = receiver['department_id']

    # 1. Student can ONLY chat with their assigned supervisor
    if s_role == 'student':
        if r_role != 'supervisor':
            return False
        # Check if receiver is their supervisor
        query = """
            SELECT cs.supervisor_id 
            FROM group_members gm
            JOIN `groups` g ON gm.group_id = g.id
            JOIN class_supervisors cs ON g.class_id = cs.class_id AND g.pbl_subject_id = cs.pbl_subject_id
            WHERE gm.student_id = %s AND cs.supervisor_id = %s AND gm.invite_status = 'accepted'
        """
        res = await Database.execute(query, (sender_id, receiver_id))
        return len(res) > 0

    # 2. Supervisor Chat
    if s_role == 'supervisor':
        # Can chat with assigned students
        if r_role == 'student':
            query = """
                SELECT gm.student_id 
                FROM group_members gm
                JOIN `groups` g ON gm.group_id = g.id
                JOIN class_supervisors cs ON g.class_id = cs.class_id AND g.pbl_subject_id = cs.pbl_subject_id
                WHERE cs.supervisor_id = %s AND gm.student_id = %s AND gm.invite_status = 'accepted'
            """
            res = await Database.execute(query, (sender_id, receiver_id))
            return len(res) > 0
        
        # Can chat with other supervisors
        if r_role == 'supervisor':
            return True # In same university/system
            
        # Can chat with PBL managers of their department
        if r_role == 'pbl_manager':
            return s_dept == r_dept

    # 3. PBL Manager Chat
    if s_role == 'pbl_manager':
        # Can chat with supervisors of their department and chairman
        if r_role == 'supervisor':
            return s_dept == r_dept
        if r_role == 'chairman':
            return True
        return False

    # 4. Chairman Chat
    if s_role == 'chairman':
        return r_role in ['supervisor', 'pbl_manager', 'chairman']
    if r_role == 'chairman':
        return s_role in ['supervisor', 'pbl_manager', 'chairman']

    return False

@app.websocket("/ws/{user_id}/{role}")
async def websocket_endpoint(websocket: WebSocket, user_id: int, role: str):
    user = await get_user_details(user_id)
    if not user or user['role'] != role:
        await websocket.close(code=4003)
        return

    await manager.connect(websocket, user_id)
    
    # Update online status in DB
    await Database.execute_commit("INSERT INTO online_users (user_id) VALUES (%s) ON DUPLICATE KEY UPDATE last_seen = CURRENT_TIMESTAMP", (user_id,))
    
    # Notify others that user is online
    await manager.broadcast({"type": "status", "user_id": user_id, "status": "online"}, exclude_user=user_id)

    try:
        while True:
            data = await websocket.receive_text()
            message_data = json.loads(data)
            
            msg_type = message_data.get("type")
            
            if msg_type == "chat_message":
                try:
                    receiver_id = int(message_data.get("receiver_id"))
                except (ValueError, TypeError):
                    await manager.send_personal_message({"type": "error", "message": "Invalid receiver ID"}, user_id)
                    continue

                content = message_data.get("message")
                
                # Check permissions
                if await validate_chat_permission(user_id, receiver_id):
                    # Find or create conversation
                    # For direct chat, we check if a conversation exists between these two
                    query = """
                        SELECT cp1.conversation_id 
                        FROM conversation_participants cp1
                        JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
                        JOIN conversations c ON cp1.conversation_id = c.id
                        WHERE cp1.user_id = %s AND cp2.user_id = %s AND c.type = 'direct'
                    """
                    conv_res = await Database.execute(query, (user_id, receiver_id))
                    
                    if conv_res:
                        conv_id = conv_res[0]['conversation_id']
                    else:
                        conv_id = await Database.execute_commit("INSERT INTO conversations (type) VALUES ('direct')")
                        await Database.execute_commit("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (%s, %s), (%s, %s)", 
                                                       (conv_id, user_id, conv_id, receiver_id))
                    
                    # Persist message
                    msg_id = await Database.execute_commit(
                        "INSERT INTO messages (conversation_id, sender_id, receiver_id, message) VALUES (%s, %s, %s, %s)",
                        (conv_id, user_id, receiver_id, content)
                    )
                    
                    response = {
                        "type": "chat_message",
                        "id": msg_id,
                        "conversation_id": conv_id,
                        "sender_id": user_id,
                        "receiver_id": receiver_id,
                        "message": content,
                        "timestamp": datetime.now().isoformat(),
                        "seen_status": 0
                    }
                    
                    # Send to receiver
                    await manager.send_personal_message(response, receiver_id)
                    # Send back to sender for confirmation/sync
                    await manager.send_personal_message(response, user_id)
                else:
                    await manager.send_personal_message({"type": "error", "message": "Permission denied"}, user_id)

            elif msg_type == "typing":
                receiver_id = message_data.get("receiver_id")
                await manager.send_personal_message({
                    "type": "typing",
                    "sender_id": user_id,
                    "is_typing": message_data.get("is_typing", False)
                }, receiver_id)

            elif msg_type == "mark_seen":
                msg_id = message_data.get("message_id")
                await Database.execute_commit("UPDATE messages SET seen_status = 1 WHERE id = %s", (msg_id,))
                # Notify sender that message was seen
                sender_id = message_data.get("sender_id")
                await manager.send_personal_message({
                    "type": "seen",
                    "message_id": msg_id,
                    "seen_by": user_id
                }, sender_id)

    except WebSocketDisconnect:
        manager.disconnect(websocket, user_id)
        # Update DB
        await Database.execute_commit("DELETE FROM online_users WHERE user_id = %s", (user_id,))
        # Notify others
        await manager.broadcast({"type": "status", "user_id": user_id, "status": "offline"})
    except Exception as e:
        print(f"Error: {e}")
        manager.disconnect(websocket, user_id)

@app.get("/history/{user1}/{user2}")
async def get_direct_chat_history(user1: int, user2: int, limit: int = 50):
    # Find conversation ID first
    query = """
        SELECT cp1.conversation_id 
        FROM conversation_participants cp1
        JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
        JOIN conversations c ON cp1.conversation_id = c.id
        WHERE cp1.user_id = %s AND cp2.user_id = %s AND c.type = 'direct'
    """
    conv_res = await Database.execute(query, (user1, user2))
    
    if not conv_res:
        return []
        
    conv_id = conv_res[0]['conversation_id']
    query = "SELECT * FROM messages WHERE conversation_id = %s ORDER BY timestamp DESC LIMIT %s"
    messages = await Database.execute(query, (conv_id, limit))
    # Convert timestamps to string for JSON serialization
    for m in messages:
        if isinstance(m['timestamp'], datetime):
            m['timestamp'] = m['timestamp'].isoformat()
            
    return sorted(messages, key=lambda x: x['timestamp'])

@app.get("/history/{conv_id}")

@app.get("/contacts/{user_id}")
async def get_contacts(user_id: int):
    user = await get_user_details(user_id)
    if not user: return []
    
    role = user['role']
    dept_id = user['department_id']
    
    contacts = []
    
    if role == 'student':
        # Get assigned supervisor
        query = """
            SELECT u.id, u.name, u.role, 'supervisor' as type
            FROM users u
            JOIN class_supervisors cs ON u.id = cs.supervisor_id
            JOIN `groups` g ON cs.class_id = g.class_id AND cs.pbl_subject_id = g.pbl_subject_id
            JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.student_id = %s AND gm.invite_status = 'accepted'
        """
        contacts = await Database.execute(query, (user_id,))
        
    elif role == 'supervisor':
        # Get assigned students
        q1 = """
            SELECT DISTINCT u.id, u.name, u.role, 'student' as type
            FROM users u
            JOIN group_members gm ON u.id = gm.student_id
            JOIN `groups` g ON gm.group_id = g.id
            JOIN class_supervisors cs ON g.class_id = cs.class_id AND g.pbl_subject_id = cs.pbl_subject_id
            WHERE cs.supervisor_id = %s AND gm.invite_status = 'accepted'
        """
        # Other supervisors
        q2 = "SELECT id, name, role, 'supervisor' as type FROM users WHERE role = 'supervisor' AND id != %s"
        # Managers of same dept
        q3 = "SELECT id, name, role, 'manager' as type FROM users WHERE role = 'pbl_manager' AND department_id = %s"
        # Chairman
        q4 = "SELECT id, name, role, 'chairman' as type FROM users WHERE role = 'chairman'"
        
        c1 = await Database.execute(q1, (user_id,))
        c2 = await Database.execute(q2, (user_id,))
        c3 = await Database.execute(q3, (dept_id,))
        c4 = await Database.execute(q4)
        
        contacts = list(c1 or []) + list(c2 or []) + list(c3 or []) + list(c4 or [])

    elif role == 'pbl_manager':
        # Supervisors of same dept
        q1 = "SELECT id, name, role, 'supervisor' as type FROM users WHERE role = 'supervisor' AND department_id = %s"
        # Chairman
        q2 = "SELECT id, name, role, 'chairman' as type FROM users WHERE role = 'chairman'"
        c1 = await Database.execute(q1, (dept_id,))
        c2 = await Database.execute(q2)
        contacts = list(c1 or []) + list(c2 or [])
        
    elif role == 'chairman':
        # All supervisors and managers
        query = "SELECT id, name, role, role as type FROM users WHERE role IN ('supervisor', 'pbl_manager')"
        contacts = await Database.execute(query)

    # Add online status
    for c in contacts:
        c['is_online'] = manager.is_online(c['id'])
        
    return contacts

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8765)
