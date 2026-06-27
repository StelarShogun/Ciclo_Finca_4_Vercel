@echo off
setlocal
set "PHP_EXE=%LOCALAPPDATA%\Programs\PHP\current\php.exe"
if exist "%PHP_EXE%" (
  "%PHP_EXE%" "%~dp0artisan" serve %*
) else (
  php "%~dp0artisan" serve %*
)
