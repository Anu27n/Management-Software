@echo off
title School Management - Port 8080
cd /d "%~dp0school-management"

set PHP=D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set INI=%~dp0school-management\php-cli.ini

echo Freeing port 8080...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8080') do taskkill /F /PID %%a 2>nul
timeout /t 2 /nobreak >nul

echo.
echo ============================================
echo   School Management - http://127.0.0.1:8080
echo ============================================
echo   Installer: http://127.0.0.1:8080/installer.php
echo   Keep this window OPEN. Ctrl+C to stop.
echo ============================================
echo.

"%PHP%" -c "%INI%" artisan serve --port=8080

echo.
pause
