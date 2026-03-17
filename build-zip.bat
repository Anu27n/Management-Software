@echo off
REM Build SchoolMS.zip using Laragon PHP and Composer
REM Run this from Laragon Cmder or double-click (Laragon must be at D:\laragon)

set LARAGON=D:\laragon
set PHP=%LARAGON%\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set COMPOSER=%LARAGON%\bin\composer\composer.phar
set PROJECT=%~dp0
set SCHOOL=%PROJECT%school-management
set STAGE=%PROJECT%tmp_build_schoolms

if not exist "%PHP%" (
    echo PHP not found at %PHP%
    echo Edit build-zip.bat if your Laragon PHP path is different.
    pause
    exit /b 1
)

echo Using PHP: %PHP%
echo Project: %PROJECT%
echo.

cd /d "%SCHOOL%"

REM Use php-cli.ini so extensions (openssl, gd, zip, etc.) load when PHP is run from CLI
set PHP_INI=%SCHOOL%\php-cli.ini
if exist "%PHP_INI%" (
    echo Using config: php-cli.ini
    "%PHP%" -c "%PHP_INI%" "%COMPOSER%" install --no-dev --optimize-autoloader --no-interaction
) else (
    "%PHP%" "%COMPOSER%" install --no-dev --optimize-autoloader --no-interaction
)
if errorlevel 1 goto fail
goto after_composer
:fail
echo Composer install failed.
pause
exit /b 1
:after_composer
echo.
echo [2/3] Copying installer.php to public...
copy /Y "%PROJECT%installer.php" "%SCHOOL%\public\installer.php" >nul

echo.
echo [3/3] Preparing clean package...
if exist "%STAGE%" rmdir /s /q "%STAGE%"
robocopy "%SCHOOL%" "%STAGE%\school-management" /E /XD ".git" "node_modules" /XF ".env" ".env.*" "php-cli.ini" ".phpunit.result.cache" >nul
if exist "%STAGE%\school-management\bootstrap\cache\config.php" del /f /q "%STAGE%\school-management\bootstrap\cache\config.php"
if exist "%STAGE%\school-management\bootstrap\cache\routes-v7.php" del /f /q "%STAGE%\school-management\bootstrap\cache\routes-v7.php"
if exist "%STAGE%\school-management\bootstrap\cache\packages.php" del /f /q "%STAGE%\school-management\bootstrap\cache\packages.php"
if exist "%STAGE%\school-management\bootstrap\cache\services.php" del /f /q "%STAGE%\school-management\bootstrap\cache\services.php"
del /f /q "%STAGE%\school-management\storage\logs\*.log" 2>nul

echo Creating SchoolMS.zip...
powershell -NoProfile -Command "Compress-Archive -Path '%STAGE%\school-management' -DestinationPath '%PROJECT%SchoolMS.zip' -Force"
if exist "%STAGE%" rmdir /s /q "%STAGE%"

if exist "%PROJECT%SchoolMS.zip" (
    echo.
    echo Done. Zip saved to: %PROJECT%SchoolMS.zip
) else (
    echo Zip creation failed.
)
pause
