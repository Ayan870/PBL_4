# PBL Management System - Real-time Chat Server

This is a FastAPI-based WebSocket server for real-time messaging.

## Setup Instructions

1. **Install Python**: Ensure you have Python 3.8+ installed.
2. **Install Dependencies**:
   ```bash
   pip install -r requirements.txt
   ```
3. **Run the Server**:
   ```bash
   uvicorn websocket_server:app --host 0.0.0.0 --port 8765 --reload
   ```

## Features
- **Real-time Messaging**: Using WebSockets for instant delivery.
- **Role-based Authorization**:
  - Students can only chat with assigned supervisors.
  - Supervisors can chat with their students, other supervisors, and department managers.
  - Managers can chat with supervisors of their department.
- **Persistence**: Messages are stored in the MySQL database.
- **Typing Indicators**: Real-time feedback when someone is typing.
- **Online/Offline Status**: Real-time tracking of active users.

## Troubleshooting
- **Database Connection**: Ensure your MySQL server is running (XAMPP). The server tries port 3306 and 3307 by default.
- **WebSocket Port**: Ensure port 8765 is not blocked by a firewall.
- **CORS**: The server is configured to allow connections from any origin for development.
