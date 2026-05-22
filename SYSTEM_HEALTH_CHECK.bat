@echo off
:: Hwange Diocesan Sacramental Database System - Pre-Flight Health Check
echo [MISSION STATUS] Initializing System Diagnostic...
.\php\php.exe actions\system_health_check.php
pause
