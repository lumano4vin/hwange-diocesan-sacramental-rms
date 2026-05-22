@echo off
setlocal enabledelayedexpansion

:: Hwange Diocesan Sacramental Database System - Backup Utility
set BACKUP_DIR=Backups
set TIMESTAMP=%DATE:~10,4%%DATE:~4,2%%DATE:~7,2%_%TIME:~0,2%%TIME:~3,2%
set TIMESTAMP=%TIMESTAMP: =0%
set FILENAME=database_backup_%TIMESTAMP%.sqlite

if not exist %BACKUP_DIR% (
    mkdir %BACKUP_DIR%
)

echo [MISSION STATUS] Starting Sacred Archive Backup...
copy database.sqlite %BACKUP_DIR%\%FILENAME% > nul

if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Database backed up successfully to %BACKUP_DIR%\%FILENAME%
    echo Your canonical records are safe.
) else (
    echo [ERROR] Backup failed! Please ensure database.sqlite exists in the root folder.
)

pause
