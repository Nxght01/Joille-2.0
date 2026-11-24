<?php
/** php scripts/migrate-estimate.php --apply: backup e migration 006. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';
$db = Source\Core\Connect::getInstance();
if (in_array('preliminary_value', $db->query('SHOW COLUMNS FROM service_requests')->fetchAll(PDO::FETCH_COLUMN), true)) {
    echo "Migration 006 já aplicada.\n"; exit;
}
echo 'Banco: ' . CONF_DB_NAME . ". Adicionar estimativa de projeto.\n";
if (!in_array('--apply', $argv, true)) { echo "Use --apply para aplicar com backup.\n"; exit; }
$backup = dirname(__DIR__) . '/banco de dados atualizado/backup-pre-006-' . date('Ymd-His') . '.sql';
$stream = fopen($backup, 'xb');
if (!$stream) throw new RuntimeException('Backup indisponível; migração cancelada.');
$write = static function(string $text) use ($stream): void {
    if (fwrite($stream, $text) !== strlen($text)) throw new RuntimeException('Falha ao gravar backup.');
};
try {
    $db->beginTransaction();
    $definition = $db->query('SHOW CREATE TABLE service_requests')->fetch(PDO::FETCH_NUM)[1];
    $write("-- Backup da tabela afetada pela migration 006\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nDROP TABLE IF EXISTS service_requests;\n$definition;\n");
    foreach ($db->query('SELECT * FROM service_requests')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $columns = implode(',', array_map(static fn($name) => '`' . str_replace('`','``',$name) . '`', array_keys($row)));
        $values = implode(',', array_map(static fn($value) => $value === null ? 'NULL' : $db->quote((string)$value), array_values($row)));
        $write("INSERT INTO service_requests ($columns) VALUES ($values);\n");
    }
    $write("SET FOREIGN_KEY_CHECKS=1;\n");
    if (!fflush($stream)) throw new RuntimeException('Falha ao concluir backup.');
    fclose($stream);
    $db->commit();
} catch (Throwable $error) {
    if (is_resource($stream)) fclose($stream);
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
echo "Backup salvo: $backup\n";
$db->exec(file_get_contents(dirname(__DIR__) . '/banco de dados atualizado/migration-006-project-estimate.sql'));
echo "Estimativa preliminar habilitada.\n";
