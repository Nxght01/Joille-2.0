$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $projectRoot
$env:JOILLE_CONFIG_FILE = Join-Path $PSScriptRoot 'support\config.php'
$env:JOILLE_BASE_URL = 'http://127.0.0.1:8086'
$env:XDEBUG_MODE = 'off'
& 'C:\wamp64\bin\php\php8.3.28\php.exe' (Join-Path $PSScriptRoot 'support\verify-isolation.php')
if ($LASTEXITCODE -ne 0) { throw 'Banco não isolado; testes recusados.' }
node --test poo-test.mjs http-contract-test.mjs image-test.mjs tests/crud-visibility-test.mjs
if ($LASTEXITCODE -ne 0) { throw 'Falha nos testes JavaScript.' }
node tests/delete-service-test.mjs
if ($LASTEXITCODE -ne 0) { throw 'Falha no teste de exclusão de serviços.' }
& 'C:\wamp64\bin\php\php8.3.28\php.exe' api-test.php
if ($LASTEXITCODE -ne 0) { throw 'Falha nos testes da API.' }
node tests/acceptance.mjs
if ($LASTEXITCODE -ne 0) { throw 'Falha nos testes de aceite.' }
