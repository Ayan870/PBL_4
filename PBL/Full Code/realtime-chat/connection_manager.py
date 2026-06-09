from typing import Dict, List, Set
from fastapi import WebSocket

class ConnectionManager:
    def __init__(self):
        # user_id -> set of active WebSockets (allows multiple tabs)
        self.active_connections: Dict[int, Set[WebSocket]] = {}
        # user_id -> online status
        self.online_users: Set[int] = set()

    async def connect(self, websocket: WebSocket, user_id: int):
        await websocket.accept()
        if user_id not in self.active_connections:
            self.active_connections[user_id] = set()
        self.active_connections[user_id].add(websocket)
        self.online_users.add(user_id)

    def disconnect(self, websocket: WebSocket, user_id: int):
        if user_id in self.active_connections:
            self.active_connections[user_id].remove(websocket)
            if not self.active_connections[user_id]:
                del self.active_connections[user_id]
                self.online_users.discard(user_id)

    async def send_personal_message(self, message: dict, user_id: int):
        if user_id in self.active_connections:
            for connection in self.active_connections[user_id]:
                await connection.send_json(message)

    async def broadcast(self, message: dict, exclude_user: int = None):
        for user_id, connections in self.active_connections.items():
            if user_id != exclude_user:
                for connection in connections:
                    await connection.send_json(message)

    def is_online(self, user_id: int) -> bool:
        return user_id in self.online_users

manager = ConnectionManager()
