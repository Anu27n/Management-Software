@echo off
cd /d "%~dp0"
set "OUT=Mark2.zip"
set "SRC=school-management"
set "STAGE=tmp_build_mark2"

set "SZ="
if exist "C:\Program Files\7-Zip\7z.exe" set "SZ=C:\Program Files\7-Zip\7z.exe"
if exist "C:\PROGRA~2\7-Zip\7z.exe" set "SZ=C:\PROGRA~2\7-Zip\7z.exe"

if not "%SZ%"=="" goto use7z
echo 7-Zip not found. Using PowerShell...
if exist "%STAGE%" rmdir /s /q "%STAGE%"
robocopy "%SRC%" "%STAGE%\%SRC%" /E /XD ".git" "node_modules" /XF ".env" ".env.*" "php-cli.ini" ".phpunit.result.cache" >nul
if exist "%STAGE%\%SRC%\bootstrap\cache\config.php" del /f /q "%STAGE%\%SRC%\bootstrap\cache\config.php"
if exist "%STAGE%\%SRC%\bootstrap\cache\routes-v7.php" del /f /q "%STAGE%\%SRC%\bootstrap\cache\routes-v7.php"
if exist "%STAGE%\%SRC%\bootstrap\cache\packages.php" del /f /q "%STAGE%\%SRC%\bootstrap\cache\packages.php"
if exist "%STAGE%\%SRC%\bootstrap\cache\services.php" del /f /q "%STAGE%\%SRC%\bootstrap\cache\services.php"
del /f /q "%STAGE%\%SRC%\storage\logs\*.log" 2>nul
powershell -NoProfile -Command "Compress-Archive -Path '%~dp0%STAGE%\%SRC%' -DestinationPath '%~dp0Mark2.zip' -Force"
if exist "%STAGE%" rmdir /s /q "%STAGE%"
goto done

:use7z
echo Using 7-Zip (store mode = fast)...
del /f "%OUT%" 2>nul
REM -mx=0 = store only (no compression), -mmt=on = multi-thread = much faster
"%SZ%" a -tzip -r -mx=0 -mmt=on "%OUT%" "%SRC%" -xr!"%SRC%\.env" -xr!"%SRC%\.env.*" -xr!"%SRC%\.git" -xr!"%SRC%\node_modules" -xr!"%SRC%\php-cli.ini" -xr!"%SRC%\storage\logs\*.log" -xr!"%SRC%\.phpunit.result.cache" -xr!"%SRC%\bootstrap\cache\config.php" -xr!"%SRC%\bootstrap\cache\routes-v7.php" -xr!"%SRC%\bootstrap\cache\packages.php" -xr!"%SRC%\bootstrap\cache\services.php"

:done
if exist "%OUT%" (
    echo.
    echo Done: %OUT%
    for %%A in ("%OUT%") do echo Size: %%~zA bytes
) else (
    echo Build failed.
)
pause
