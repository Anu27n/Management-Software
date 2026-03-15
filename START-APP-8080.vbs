Set WshShell = CreateObject("WScript.Shell")
WshShell.CurrentDirectory = "D:\Management-Software"
WshShell.Run "cmd.exe /k ""cd /d D:\Management-Software\school-management && D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -c D:\Management-Software\school-management\php-cli.ini artisan serve --port=8080""", 1, False
