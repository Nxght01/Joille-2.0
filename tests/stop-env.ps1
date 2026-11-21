$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$state = Join-Path $projectRoot '.runtime\processes.json'
if (!(Test-Path $state)) { Write-Output 'Nenhum ambiente de teste registrado.'; exit }
$processes = Get-Content -LiteralPath $state -Raw | ConvertFrom-Json
foreach ($kind in @('php','mysql')) {
    $processId = $processes.$kind
    if (!$processId) { continue }
    $process = Get-CimInstance Win32_Process -Filter "ProcessId = $processId" -ErrorAction SilentlyContinue
    # Nunca encerra Wamp ou processos cuja linha de comando não pertença a esta pasta.
    if ($process -and $process.CommandLine.Contains($projectRoot) -and $process.Name -in @('php.exe','mysqld.exe')) {
        Stop-Process -Id $processId
    }
}
Remove-Item -LiteralPath $state
Write-Output 'Servidores de teste encerrados; dados descartáveis preservados em .runtime.'
