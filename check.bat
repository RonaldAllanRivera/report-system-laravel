@echo off
echo === Basic System Check ===
echo.

echo 1. Checking PHP...
where php
echo PHP status: %ERRORLEVEL%
echo.

echo 2. Checking Composer...
where composer
echo Composer status: %ERRORLEVEL%
echo.

echo 3. Running Composer...
composer --version
echo Composer version status: %ERRORLEVEL%
echo.

echo 4. Creating a test file...
echo Test content > test-file.txt
type test-file.txt
echo File creation status: %ERRORLEVEL%
echo.

echo === Check completed ===
pause
