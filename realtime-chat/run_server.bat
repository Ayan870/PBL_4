@echo off
title PBL Chat Server
echo Starting Real-time Chat Server...
cd /d "%~dp0"

echo.
echo Starting FastAPI server on port 8765...
echo (If you see 'Application startup complete', the chat is working!)
echo.

python -m uvicorn websocket_server:app --host 0.0.0.0 --port 8765 --reload

pause
