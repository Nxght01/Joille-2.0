<?php
ob_start();
require __DIR__ . '/verify-isolation.php';
ob_end_clean();
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$stmt = $db->prepare($input['sql']);
$stmt->execute($input['params'] ?? []);
echo json_encode($stmt->columnCount() ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []);
