param([string]$Php = 'C:\wamp64\bin\php\php8.3.28\php.exe')
$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$secretFile = Join-Path $projectRoot 'source\Config\Mail.secret.php'
Write-Host 'Configurar envio do Joille por joilleifsul@gmail.com.'
Write-Host 'Use a senha de app de 16 caracteres do Google, não a senha normal da conta.'
$gmailSecret = Read-Host 'Cole a senha de app (entrada oculta)' -AsSecureString
$secretPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($gmailSecret)
try {
    $gmailPlain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($secretPointer) -replace '\s', ''
    if ($gmailPlain -notmatch '^[a-zA-Z0-9]{16}$') { throw 'A senha de app precisa conter 16 caracteres, sem os espaços de separação.' }
    $phpSecret = "<?php`r`n// Credencial local: não versionar nem compartilhar.`r`nreturn '" + $gmailPlain + "';`r`n"
    [IO.File]::WriteAllText($secretFile, $phpSecret, [Text.UTF8Encoding]::new($false))
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($secretPointer)
    $gmailPlain = $null
    $phpSecret = $null
    $gmailSecret.Dispose()
}
Write-Host 'Credencial salva no arquivo local ignorado pelo Git.'
Write-Host 'Enviando uma mensagem de teste para joilleifsul@gmail.com...'
$env:XDEBUG_MODE = 'off'
& $Php (Join-Path $PSScriptRoot 'test-mail.php') --send
if ($LASTEXITCODE -ne 0) { throw 'O teste falhou. Confira a mensagem acima.' }
