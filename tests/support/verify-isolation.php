<?php
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$db = \Source\Core\Connect::getInstance();
$expected = realpath(dirname(__DIR__, 2) . '/.runtime/db');
if (!$expected || realpath($db->query('SELECT @@datadir')->fetchColumn()) !== $expected || CONF_DB_NAME !== 'joille_test') {
    throw new RuntimeException('Os testes só podem usar o banco descartável.');
}
echo "Isolamento do banco confirmado.\n";
