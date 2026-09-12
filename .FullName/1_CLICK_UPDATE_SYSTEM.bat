@echo off
title Hwange Diocesan RMS - Easy Update Tool
color 0B

echo ===================================================
echo    HWANGE DIOCESAN RMS - AUTOMATIC UPDATE TOOL
echo ===================================================
echo.
echo  This tool will safely update your system to the 
echo  latest version while PROTECTING your existing 
echo  parishioners' records.
echo.
echo  Checking for existing data...

if exist "..\database.sqlite" (
    echo [FOUND] Existing database found in the parent folder.
    echo [ACTION] Safely copying your records to the new version...
    copy "..\database.sqlite" "database.sqlite" /Y
    echo.
    echo ===================================================
    echo    SUCCESS: YOUR DATA HAS BEEN MIGRATED!
    echo ===================================================
) else (
    echo [NOTICE] No old database found in the parent folder.
    echo [NOTICE] This appears to be a fresh installation.
)

echo.
echo  The update is now complete. 
echo  You can now close this window and log in.
echo.
pause
