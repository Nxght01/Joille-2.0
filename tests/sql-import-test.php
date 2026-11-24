<?php
// Executar após tests/start-env.ps1. Nunca conecta ao banco do Wamp.
$root = dirname(__DIR__);
$db = new PDO('mysql:host=127.0.0.1;port=33316;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$expected = realpath($root . '/.runtime/db');
if (!$expected || realpath($db->query('SELECT @@datadir')->fetchColumn()) !== $expected) {
    throw new RuntimeException('Recusado: servidor não pertence ao ambiente descartável.');
}
$schema = 'joille_import_test';
$sql = str_replace('`joille`', '`' . $schema . '`', file_get_contents($root . '/banco de dados atualizado/joille.sql'));
$migration = file_get_contents($root . '/banco de dados atualizado/migration-005-agency-catalog.sql');
function check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "OK: {$message}\n";
}
try {
    foreach ([1, 0] as $safe) {
        $db->exec("SET SESSION SQL_SAFE_UPDATES = {$safe}");
        $db->exec($sql);
        check((int)$db->query('SELECT @@SESSION.SQL_SAFE_UPDATES')->fetchColumn() === $safe, "Importação restaura Safe Updates={$safe}");
        check((int)$db->query('SELECT COUNT(*) FROM services WHERE active=1')->fetchColumn() === 6, 'Seis serviços ativos');
        check((int)$db->query('SELECT COUNT(*) FROM categories WHERE active=1')->fetchColumn() === 4, 'Quatro categorias ativas');
        check((int)$db->query('SELECT COUNT(*) FROM questions WHERE active=1')->fetchColumn() === 6, 'Seis FAQs ativas');
        check((bool)$db->query("SHOW COLUMNS FROM service_requests LIKE 'preliminary_value'")->fetch(), 'Valor preliminar disponível');
        $db->exec($migration);
        check((int)$db->query('SELECT @@SESSION.SQL_SAFE_UPDATES')->fetchColumn() === $safe, "Migration restaura Safe Updates={$safe}");
        check((int)$db->query('SELECT COUNT(*) FROM services WHERE active=1')->fetchColumn() === 6, 'Migration não duplica catálogo');
    }
} finally {
    $db->exec('DROP DATABASE IF EXISTS `joille_import_test`');
}
