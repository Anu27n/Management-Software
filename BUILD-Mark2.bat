@echo off
cd /d "%~dp0"
set "OUT=Mark2.zip"
set "SRC=school-management"

set "SZ="
if exist "C:\Program Files\7-Zip\7z.exe" set "SZ=C:\Program Files\7-Zip\7z.exe"
if exist "C:\PROGRA~2\7-Zip\7z.exe" set "SZ=C:\PROGRA~2\7-Zip\7z.exe"

if not "%SZ%"=="" goto use7z
echo 7-Zip not found. Using PowerShell...
powershell -NoProfile -Command "Compress-Archive -Path '%~dp0school-management' -DestinationPath '%~dp0Mark2.zip' -Force"
goto done

:use7z
echo Using 7-Zip (store mode = fast)...
del /f "%OUT%" 2>nul
REM -mx=0 = store only (no compression), -mmt=on = multi-thread = much faster
"%SZ%" a -tzip -r -mx=0 -mmt=on "%OUT%" "%SRC%" -xr!"%SRC%\.env" -xr!"%SRC%\.env.*" -xr!"%SRC%\.git" -xr!"%SRC%\node_modules" -xr!"%SRC%\php-cli.ini" -xr!"%SRC%\storage\logs\*.log" -xr!"%SRC%\.phpunit.result.cache"

:done
if exist "%OUT%" (
    echo.
    echo Done: %OUT%
    for %%A in ("%OUT%") do echo Size: %%~zA bytes
) else (
    echo Build failed.
)
pause
