@echo off
TITLE Hwange Diocese RMS - Password Fixer
echo -----------------------------------------------
echo   Hwange Diocese Records Management System
echo           Emergency Login Fixer
echo -----------------------------------------------
echo.
echo Attempting to reset password for 'Francis Xavier'...
echo.

:: Use the bundled portable PHP engine
".\php\php.exe" "includes\reset_logic.php"

echo.
echo -----------------------------------------------
echo Process finished.
pause
