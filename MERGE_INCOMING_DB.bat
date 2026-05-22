@echo off
TITLE Hwange Diocese RMS - Offline Merge Tool
COLOR 0A

echo =======================================================
echo     HWANGE DIOCESE RMS - OFFLINE DATABASE MERGE
echo =======================================================
echo.

set INCOMING_FILE=
for %%f in (incoming*.sqlite) do (
    set INCOMING_FILE=%%f
    goto :found
)

COLOR 0C
echo ERROR: Could not find any file starting with 'incoming' and ending in '.sqlite'!
echo.
echo Please make sure you have:
echo 1. Downloaded the database from the priest.
echo 2. Renamed it to start with 'incoming' (e.g. incoming_st_francis.sqlite)
echo 3. Placed it in this exact folder.
echo.
pause
exit /b

:found
echo Found incoming database: %INCOMING_FILE%
echo Beginning merge process...
echo.

python merge_parish_db.py database.sqlite "%INCOMING_FILE%"

echo.
echo =======================================================
echo If the message above says "Merge completed successfully",
echo you can now safely delete the '%INCOMING_FILE%' file.
echo =======================================================
echo.
pause
