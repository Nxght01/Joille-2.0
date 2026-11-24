<?php
/** Limpeza explícita dos registros inativos do banco configurado. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';

$db = Source\Core\Connect::getInstance();
$operations = [
    'service_images' => 'DELETE i FROM service_images i JOIN services s ON s.id=i.services_id WHERE s.active=0',
    'password_resets' => 'DELETE p FROM password_resets p JOIN users u ON u.id=p.users_id WHERE u.active=0 OR p.used_at IS NOT NULL OR p.expires_at<=NOW()',
    'questions' => 'DELETE FROM questions WHERE active=0',
    'services' => 'DELETE FROM services WHERE active=0',
    'users' => 'DELETE FROM users WHERE active=0',
    'categories' => 'DELETE FROM categories WHERE active=0',
    'types' => 'DELETE FROM types WHERE active=0',
    'users_types' => 'DELETE FROM users_types WHERE active=0',
];
$counts = [];
foreach (['services','questions','users','categories','types','users_types'] as $table) {
    $counts[$table] = (int)$db->query("SELECT COUNT(*) FROM `$table` WHERE active=0")->fetchColumn();
}
echo json_encode(['database'=>CONF_DB_NAME,'inactive'=>$counts], JSON_PRETTY_PRINT) . "\n";
if (!in_array('--apply', $argv, true)) { echo "Simulação. Para aplicar, use --apply --database=" . CONF_DB_NAME . "\n"; exit; }
if (!in_array('--database=' . CONF_DB_NAME, $argv, true)) throw new RuntimeException('Informe explicitamente o banco de destino.');

// Não transformar a remoção de inativos em exclusão silenciosa de projetos ou mensagens.
$dependencies = [
    'projetos' => 'SELECT COUNT(*) FROM service_requests r JOIN users u ON u.id=r.users_id JOIN services s ON s.id=r.services_id WHERE u.active=0 OR s.active=0',
    'mensagens' => 'SELECT COUNT(*) FROM contacts c JOIN users u ON u.id=c.users_id WHERE u.active=0',
    'vínculos antigos de usuários' => 'SELECT COUNT(*) FROM users u JOIN services s ON s.id=u.services_id WHERE s.active=0 AND u.active=1',
    'serviços em categorias inativas' => 'SELECT COUNT(*) FROM services s JOIN categories c ON c.id=s.categories_id WHERE s.active=1 AND c.active=0',
    'FAQs em tipos inativos' => 'SELECT COUNT(*) FROM questions q JOIN types t ON t.id=q.types_id WHERE q.active=1 AND t.active=0',
    'usuários em perfis inativos' => 'SELECT COUNT(*) FROM users u JOIN users_types t ON t.id=u.type_id WHERE u.active=1 AND t.active=0',
];
foreach ($dependencies as $label=>$sql) {
    if ((int)$db->query($sql)->fetchColumn() > 0) throw new RuntimeException("Limpeza cancelada: há $label vinculados aos inativos.");
}

$backup = dirname(__DIR__) . '/banco de dados atualizado/backup-pre-cleanup-' . date('Ymd-His') . '.sql';
$stream = fopen($backup, 'xb');
if (!$stream) throw new RuntimeException('Backup indisponível; limpeza cancelada.');
$write = static function(string $text) use ($stream): void {
    if (fwrite($stream, $text) !== strlen($text)) throw new RuntimeException('Falha ao gravar backup; limpeza cancelada.');
};
$identifier = static fn(string $name) => '`' . str_replace('`','``',$name) . '`';
try {
    $db->beginTransaction();
    $write("-- Backup antes da limpeza de inativos\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    foreach ($db->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $name=$identifier($table);
        $definition=$db->query("SHOW CREATE TABLE $name")->fetch(PDO::FETCH_NUM)[1];
        $write("DROP TABLE IF EXISTS $name;\n$definition;\n");
        foreach ($db->query("SELECT * FROM $name") as $object) {
            $row=(array)$object;
            $columns=implode(',',array_map($identifier,array_keys($row)));
            $values=implode(',',array_map(static fn($value)=>$value===null?'NULL':$db->quote((string)$value),array_values($row)));
            $write("INSERT INTO $name ($columns) VALUES ($values);\n");
        }
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

try {
    $db->beginTransaction();
    // Revalidar dentro da transação; as FKs continuam habilitadas.
    foreach ($dependencies as $sql) {
        if ((int)$db->query($sql)->fetchColumn() > 0) throw new RuntimeException('Os vínculos mudaram; limpeza cancelada.');
    }
    $projectsBefore=$db->query('SELECT * FROM service_requests ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $deleted=[];
    // Vínculos legados entre dois registros inativos não devem impedir sua remoção.
    $db->exec('UPDATE users u JOIN services s ON s.id=u.services_id SET u.services_id=NULL WHERE u.active=0 AND s.active=0');
    foreach ($operations as $table=>$sql) $deleted[$table]=$db->exec($sql);
    $projectsAfter=$db->query('SELECT * FROM service_requests ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    if ($projectsBefore!==$projectsAfter) throw new RuntimeException('Projetos divergentes; limpeza revertida.');
    foreach (array_keys($counts) as $table) {
        if ((int)$db->query("SELECT COUNT(*) FROM `$table` WHERE active=0")->fetchColumn()!==0) throw new RuntimeException('Restaram inativos; limpeza revertida.');
    }
    $db->commit();
    echo json_encode(['deleted'=>$deleted,'projects_preserved'=>count($projectsAfter)],JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
