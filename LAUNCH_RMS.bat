@echo off
TITLE Hwange Diocese RMS Launcher
echo -----------------------------------------
echo   Hwange Diocese Records Management System
echo -----------------------------------------
echo.
echo Attempting to start the portable server...
echo.

powershell -ExecutionPolicy Bypass -File "%~dp0RUN_PORTABLE.ps1"

echo.
echo Server stopped.
pause
