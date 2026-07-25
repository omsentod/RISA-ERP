@echo off
title RISA Standalone Barcode System Launcher
color 0A
cls
echo ====================================================
echo      RISA BARCODE SYSTEM (STANDALONE LAUNCHER)
echo ====================================================
echo.

set PYTHON_CMD=python
where python >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\laragon\bin\python\python.exe" (
        set PYTHON_CMD="C:\laragon\bin\python\python.exe"
    )
)

%PYTHON_CMD% run_server.py
pause
