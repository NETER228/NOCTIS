$ErrorActionPreference = 'Stop'
$project = Split-Path -Parent $MyInvocation.MyCommand.Path
$php = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
$db = 'C:\Program Files\MariaDB 13.0\bin\mariadbd.exe'
$dbConfig = 'C:\Program Files\MariaDB 13.0\data\my.ini'
if (-not (Test-Path -LiteralPath $php)) { throw 'PHP не найден. Установите PHP 8.2.' }
if (-not (Test-Path -LiteralPath $db)) { throw 'MariaDB не найдена. Установите MariaDB.' }
if (-not (Get-Process mariadbd -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $db -ArgumentList "`"--defaults-file=$dbConfig`"" -WindowStyle Hidden
    Start-Sleep -Seconds 3
}
if (-not (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $php -ArgumentList @('-S', '127.0.0.1:8000', '-t', "`"$project`"") -WindowStyle Hidden
    Start-Sleep -Seconds 2
}
Start-Process 'http://127.0.0.1:8000/'
