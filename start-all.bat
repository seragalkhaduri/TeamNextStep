@echo off
title UIMP + RGMS Dual Launcher
echo ===================================================
echo   UIMP + RGMS University & Research Subsystem
echo ===================================================
echo.
echo [1/2] Starting UIMP Core Server on http://127.0.0.1:8000 ...
start "UIMP Core Server" /D "C:\UIMP-RGMS\uimp-core" php artisan serve --host=127.0.0.1 --port=8000

echo [2/2] Starting RGMS Subsystem Server on http://127.0.0.1:8001 ...
start "RGMS Subsystem Server" /D "C:\UIMP-RGMS\rgms-subsystem" php artisan serve --host=127.0.0.1 --port=8001

echo.
echo Servers started successfully!
echo - UIMP Core:      http://127.0.0.1:8000
echo - RGMS Subsystem: http://127.0.0.1:8001
echo.
echo Press any key to close this launcher (servers will keep running in separate windows).
pause > nul
