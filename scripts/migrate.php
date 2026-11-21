<?php
/** php scripts/migrate.php --apply : backup integral seguido da migration 004. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';

use Source\Core\Connect;

$db = Connect::getInstance();
$columns = $db->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
$tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$missing = array_diff(['address', 'phone'], $columns);
$needsRequests = !in_array('service_requests', $tables, true);
if (!$missing && !$needsRequests) { echo "Migration 004 já aplicada.\n"; exit; }
echo 'Banco: ' . CONF_DB_NAME . '. Pendências: ' . implode(', ', [...$missing, ...($needsRequests ? ['service_requests'] : [])]) . ".\n";
if (!in_array('--apply', $argv, true)) { echo "Para aplicar com backup: php scripts/migrate.php --apply\n"; exit; }

$backup = dirname(__DIR__) . '/banco de dados atualizado/backup-pre-004-' . date('Ymd-His') . '.sql';
$stream = fopen($backup, 'xb');
if (!$stream) throw new RuntimeException('Não foi possível criar o backup. Migração cancelada.');
$quoteIdentifier = static fn(string $name) => '`' . str_replace('`', '``', $name) . '`';
try {
    $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $db->beginTransaction();
    fwrite($stream, "-- Backup local antes da migration 004\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    foreach ($tables as $table) {
        $identifier = $quoteIdentifier($table);
        $definition = $db->query("SHOW CREATE TABLE $identifier")->fetch(PDO::FETCH_NUM)[1];
        fwrite($stream, "DROP TABLE IF EXISTS $identifier;\n$definition;\n");
        $rows = $db->query("SELECT * FROM $identifier");
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $fields = implode(',', array_map($quoteIdentifier, array_keys($row)));
            $values = implode(',', array_map(static fn($value) => $value === null ? 'NULL' : $db->quote((string)$value), array_values($row)));
            if (fwrite($stream, "INSERT INTO $identifier ($fields) VALUES ($values);\n") === false) throw new RuntimeException('Falha na escrita do backup.');
        }
    }
    fwrite($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
    fflush($stream);
    fclose($stream);
    $db->commit();
} catch (Throwable $error) {
    if (is_resource($stream)) fclose($stream);
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
echo "Backup completo salvo em: $backup\n";
foreach ($missing as $column) {
    $length = $column === 'address' ? 255 : 30;
    $db->exec("ALTER TABLE users ADD COLUMN $column VARCHAR($length) DEFAULT NULL");
}
if ($needsRequests) {
    $migration = file_get_contents(dirname(__DIR__) . '/banco de dados atualizado/migration-004-service-requests.sql');
    $db->exec(substr($migration, strpos($migration, 'CREATE TABLE service_requests')));
}
echo "Migration 004 aplicada; tabelas e registros existentes preservados.\n";
