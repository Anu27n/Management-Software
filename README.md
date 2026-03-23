# Management-Software

Run using this command:
$env:DB_CONNECTION='sqlite'; $env:DB_DATABASE='D:\Management-Software\school-management\database\database.sqlite'; $env:SESSION_DRIVER='file'; $env:CACHE_STORE='file'; $env:QUEUE_CONNECTION='sync'; & "D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "D:\Management-Software\school-management\artisan" serve --host=127.0.0.1 --port=8000