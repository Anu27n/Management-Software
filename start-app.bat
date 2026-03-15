@echo off
title School Management - Laravel
cd /d "%~dp0school-management"

REM Use port 8080 to avoid conflict with Windows reserved ports (e.g. Hyper-V/WSL often block 8000)
set PORT=8080
if not "%~1"=="" set PORT=%~1

echo Starting School Management System...
echo.
echo Open in browser: http://127.0.0.1:%PORT%
echo Press Ctrl+C to stop the server.
echo.

D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -c "%~dp0school-management\php-cli.ini" artisan serve --port=%PORT%

if errorlevel 1 (
    echo.
    echo If port %PORT% failed, try: start-app.bat 5555
    echo Or run this window as Administrator (right-click - Run as administrator).
)
pause
