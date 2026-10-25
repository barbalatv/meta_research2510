@echo off
cd /d "%~dp0"
echo Iniciando Tech2Teach ExamSuite...
start "" php -S localhost:8080 -t public
timeout /t 1 >nul
start "" "http://localhost:8080"
echo Server OK. Para salir, cierra esta ventana.
pause
