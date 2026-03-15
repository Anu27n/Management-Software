@echo off
REM Create MySQL database for School Management System (Laragon)
REM 1. Start Laragon and click "Start All" so MySQL is running
REM 2. Then run this script (double-click or from Laragon Terminal)

set MYSQL="D:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
set DB_NAME=school_management
set DB_USER=root
set DB_PASS=

echo Creating MySQL database for School Management System...
echo Database: %DB_NAME%
echo User: %DB_USER%
echo.

REM Laragon default: root with no password. If you set a password, use: set DB_PASS=yourpassword
if "%DB_PASS%"=="" (
    "%MYSQL%" -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
) else (
    "%MYSQL%" -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
)

if errorlevel 1 (
    echo.
    echo Failed to create database. Make sure:
    echo   1. Laragon is running and you clicked "Start All" so MySQL is started.
    echo   2. MySQL root password: if you set one in Laragon, edit this script and set DB_PASS=yourpassword
    echo.
    pause
    exit /b 1
)

echo.
echo Database "%DB_NAME%" created successfully.
echo You can now run the app and use: php artisan migrate
echo.
pause
