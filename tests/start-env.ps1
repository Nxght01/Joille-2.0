param(
    [string]$Php = 'C:\wamp64\bin\php\php8.3.28\php.exe',
    [string]$MysqlBin = 'C:\wamp64\bin\mysql\mysql8.4.7\bin'
)
$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$runtime = Join-Path $projectRoot '.runtime'
$dataDir = Join-Path $runtime 'db'
New-Item -ItemType Directory -Force -Path $runtime | Out-Null
if (Test-Path (Join-Path $runtime 'processes.json')) { throw 'O ambiente já foi iniciado. Use tests/stop-env.ps1 primeiro.' }
if (!(Test-Path (Join-Path $dataDir 'mysql'))) {
    New-Item -ItemType Directory -Force -Path $dataDir | Out-Null
    & (Join-Path $MysqlBin 'mysqld.exe') --no-defaults --initialize-insecure "--datadir=$dataDir" --console 2> (Join-Path $runtime 'mysql-init.log')
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao inicializar o banco de teste. Veja .runtime/mysql-init.log.' }
}
$dbProcess = Start-Process -FilePath (Join-Path $MysqlBin 'mysqld.exe') -ArgumentList @('--no-defaults', "--datadir=$dataDir", '--port=33316', '--bind-address=127.0.0.1', '--mysqlx=OFF', '--console') -WindowStyle Hidden -PassThru -RedirectStandardError (Join-Path $runtime 'mysql.log') -RedirectStandardOutput (Join-Path $runtime 'mysql-out.log')
@{mysql=$dbProcess.Id;php=$null} | ConvertTo-Json | Set-Content (Join-Path $runtime 'processes.json')
$ready = $false
for ($attempt=0; $attempt -lt 30; $attempt++) {
    & (Join-Path $MysqlBin 'mysqladmin.exe') --no-defaults --host=127.0.0.1 --port=33316 -uroot ping 2>$null | Out-Null
    if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    Start-Sleep -Milliseconds 500
}
if (!$ready) { throw 'O banco descartável não iniciou. Veja .runtime/mysql.log.' }
$env:JOILLE_CONFIG_FILE = Join-Path $PSScriptRoot 'support\config.php'
$env:XDEBUG_MODE = 'off'
& $Php (Join-Path $PSScriptRoot 'support\install.php')
if ($LASTEXITCODE -ne 0) { throw 'Falha ao importar o banco de teste.' }
$webProcess = Start-Process -FilePath $Php -ArgumentList @('-d', 'display_errors=0', '-d', "error_log=$runtime/php-errors.log", '-d', 'upload_max_filesize=5M', '-d', 'post_max_size=32M', '-S', '127.0.0.1:8086', '-t', $projectRoot, (Join-Path $PSScriptRoot 'support\router.php')) -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardError (Join-Path $runtime 'php-server.log') -RedirectStandardOutput (Join-Path $runtime 'php-out.log')
@{mysql=$dbProcess.Id;php=$webProcess.Id} | ConvertTo-Json | Set-Content (Join-Path $runtime 'processes.json')
Write-Output 'Ambiente isolado: http://127.0.0.1:8086 (MySQL 33316, banco joille_test).'
