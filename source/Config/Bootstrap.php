<?php
// A configuração alternativa é definida somente pelo ambiente do processo (CLI/testes).
$configFile = getenv('JOILLE_CONFIG_FILE') ?: __DIR__ . '/Config.php';
if (!is_file($configFile)) {
    throw new RuntimeException('Configure source/Config/Config.php a partir de Config.example.php.');
}
require $configFile;
date_default_timezone_set('America/Sao_Paulo');
