<?php
// CLI only. Removes exclusively this audit's recorded IDs after checking ownership.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
if (!preg_match('/^AUDIT06-[0-9]+$/', $input['run'] ?? '')) { throw new RuntimeException('Invalid run'); }
$columns = ['services'=>'name', 'categories'=>'name', 'questions'=>'question', 'users'=>'name'];
$db = Source\Core\Connect::getInstance();
$db->beginTransaction();
try {
    // Reverse creation order keeps services before their category.
    foreach (array_reverse($input['created']) as $record) {
        $table = $record['resource'];
        if (!isset($columns[$table]) || !is_int($record['id']) || $record['id'] < 1) { throw new RuntimeException('Invalid record'); }
        $column = $columns[$table];
        $check = $db->prepare("SELECT {$column} FROM {$table} WHERE id = ?");
        $check->execute([$record['id']]);
        $name = $check->fetchColumn();
        if (!is_string($name) || !str_starts_with($name, $input['run'] . ' ')) { throw new RuntimeException('Record ownership mismatch'); }
        $delete = $db->prepare("DELETE FROM {$table} WHERE id = ? AND {$column} = ?");
        $delete->execute([$record['id'], $name]);
        if ($delete->rowCount() !== 1) { throw new RuntimeException('Cleanup mismatch'); }
    }
    $db->commit();
    echo "Exact audit records removed\n";
} catch (Throwable $error) { $db->rollBack(); throw $error; }
