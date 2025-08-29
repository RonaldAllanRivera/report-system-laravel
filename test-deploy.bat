@echo off
echo [%DATE% %TIME%] Starting test deployment...
echo.

echo === Checking PHP ===
where php
echo PHP status: %ERRORLEVEL%
echo.

echo === Checking Composer ===
where composer
echo Composer status: %ERRORLEVEL%
echo.

if exist composer.phar (
    echo Using local composer.phar
    set COMPOSER=php composer.phar
) else (
    echo Using global composer
    set COMPOSER=composer
)

echo === Running Composer Install ===
%COMPOSER% --version
echo Composer version status: %ERRORLEVEL%
echo.

%COMPOSER% install --no-interaction --no-progress
echo Composer install status: %ERRORLEVEL%
echo.

echo === Checking .env ===
if not exist .env (
    echo .env not found, copying from .env.example
    copy /Y .env.example .env
    echo Copy status: %ERRORLEVEL%
) else (
    echo .env already exists
)
echo.

echo === Generating Application Key ===
php artisan key:generate --show
echo Key generation status: %ERRORLEVEL%
echo.

echo === Test deployment completed ===
pause
