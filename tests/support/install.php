<?php
require __DIR__ . '/config.php';
$db = new PDO('mysql:host=127.0.0.1;port=33316;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$dataDir = $db->query('SELECT @@datadir')->fetchColumn();
$expected = realpath(dirname(__DIR__, 2) . '/.runtime/db');
if (realpath($dataDir) !== $expected || !$expected) throw new RuntimeException('Recusado: o servidor não é o banco descartável.');
if ($db->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'joille_test'")->fetchColumn()) {
    echo "Banco de teste existente preservado.\n";
    exit;
}
$sql = file_get_contents(dirname(__DIR__, 2) . '/banco de dados atualizado/joille.sql');
$db->exec(str_replace('`joille`', '`joille_test`', $sql));
echo "Banco descartável instalado.\n";
