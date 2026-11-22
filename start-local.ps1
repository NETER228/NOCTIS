$ErrorActionPreference = 'Stop'
$project = Split-Path -Parent $MyInvocation.MyCommand.Path
$php = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
$db = 'C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe'
$dbDir = Join-Path $env:LOCALAPPDATA 'NOCTIS-MySQL-Data'
if (-not (Test-Path -LiteralPath $php)) { throw 'PHP не найден. Установите PHP 8.2.' }
if (-not (Test-Path -LiteralPath $db)) { throw 'MySQL не найден. Установите MySQL 8.4.' }
if (-not (Test-Path -LiteralPath $dbDir)) { throw 'Локальная база NOCTIS не найдена.' }
if (-not (Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $db -ArgumentList @("`"--datadir=$dbDir`"", '--port=3307', '--bind-address=127.0.0.1') -WindowStyle Hidden
    Start-Sleep -Seconds 3
}
if (-not (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $php -ArgumentList @('-S', '127.0.0.1:8000', '-t', "`"$project`"") -WindowStyle Hidden
    Start-Sleep -Seconds 2
}
Start-Process 'http://127.0.0.1:8000/'
