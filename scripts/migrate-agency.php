<?php
/** Backup integral e adaptação do catálogo para a agência. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';
use Source\Core\Connect;
$db = Connect::getInstance();
$tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
echo "Atualização do catálogo no banco " . CONF_DB_NAME . ".\n";
if (!in_array('--apply', $argv, true)) { echo "Use --apply para criar backup e aplicar.\n"; exit; }
$backup = dirname(__DIR__) . '/banco de dados atualizado/backup-pre-005-' . date('Ymd-His') . '.sql';
$stream = fopen($backup, 'xb');
if (!$stream) throw new RuntimeException('Não foi possível criar o backup. Migração cancelada.');
$quoteIdentifier = static fn(string $name) => '`' . str_replace('`', '``', $name) . '`';
try {
    $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $db->beginTransaction();
    fwrite($stream, "-- Backup local antes da migration 005\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
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

try {
    $db->beginTransaction();
    $db->exec(file_get_contents(dirname(__DIR__) . '/banco de dados atualizado/migration-005-agency-catalog.sql'));
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
echo "Catálogo da agência aplicado; histórico preservado.\n";
