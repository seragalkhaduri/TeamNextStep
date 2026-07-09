@echo off
title UIMP + RGMS Server
color 0A

echo ====================================
echo   UIMP + RGMS Unified System
echo ====================================
echo.

REM Check if MySQL is running
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] MySQL is not running!
    echo Please open XAMPP Control Panel and start MySQL first.
    echo.
    start "" "C:\xampp\xampp-control.exe"
    pause
    exit /b 1
)

echo [OK] MySQL is running
echo.
echo Starting Laravel server on http://127.0.0.1:8000
echo.
echo Press Ctrl+C to stop the server
echo.

set PATH=C:\xampp\php84;%PATH%
set PHPRC=C:\xampp\php84\php.ini

"C:\xampp\php84\php.exe" -c "C:\xampp\php84\php.ini" artisan serve --host=127.0.0.1 --port=8000
