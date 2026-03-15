@echo off
title School Management - Port 5000
cd /d "%~dp0school-management"

set PHP=D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set INI=%~dp0school-management\php-cli.ini

echo ============================================
echo   School Management - http://127.0.0.1:5000
echo ============================================
echo   Installer: http://127.0.0.1:5000/installer.php
echo   Keep this window OPEN. Ctrl+C to stop.
echo ============================================
echo.

"%PHP%" -c "%INI%" artisan serve --port=5000

echo.
pause
