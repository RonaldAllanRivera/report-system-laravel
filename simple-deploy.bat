@echo off
echo [%DATE% %TIME%] Starting deployment...

:: Check PHP
where php
echo PHP check result: %ERRORLEVEL%

:: Check Composer
where composer
echo Composer check result: %ERRORLEVEL%

:: Try running Composer directly
composer --version
echo Composer version check result: %ERRORLEVEL%

:: Try PHP directly
php -v
echo PHP version check result: %ERRORLEVEL%

pause
