<?php
/** Instala as ilustrações geradas nos seis serviços, preservando a galeria existente. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';
$root = dirname(__DIR__);
$sourceDir = $root . '/views/assets/public/images/services';
$catalog = json_decode(file_get_contents($sourceDir . '/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
$relativeDir = CONF_UPLOAD_DIR . '/' . CONF_UPLOAD_IMAGE_DIR . '/agency';
$destination = $root . '/' . $relativeDir;
$db = Source\Core\Connect::getInstance();
$plan = [];
foreach ($catalog['images'] as $image) {
    if (!preg_match('/^[a-z0-9-]+\.png$/D', $image['file'])) throw new RuntimeException('Nome de arquivo inválido.');
    $source = $sourceDir . '/' . $image['file'];
    $info = getimagesize($source);
    if (!$info || $info['mime'] !== 'image/png' || filesize($source) > 4 * 1024 * 1024 || $info[0]*$info[1] > 12000000) {
        throw new RuntimeException('Imagem incompatível com o upload: ' . $image['file']);
    }
    $find = $db->prepare('SELECT id FROM services WHERE name=? AND active=1');
    $find->execute([$image['name']]);
    $ids = $find->fetchAll(PDO::FETCH_COLUMN);
    if (count($ids) !== 1) throw new RuntimeException('Esperado exatamente um serviço ativo: ' . $image['name']);
    $plan[] = $image + ['service_id'=>(int)$ids[0], 'source'=>$source, 'path'=>$relativeDir.'/'.$image['file']];
}
echo 'Banco: ' . CONF_DB_NAME . '. Imagens: ' . count($plan) . "\n";
if (!in_array('--apply', $argv, true)) { echo "Simulação. Use --apply para vincular as capas.\n"; exit; }
if (!is_dir($destination) && !mkdir($destination,0755,true)) throw new RuntimeException('Não foi possível criar a pasta de imagens.');
$backupDir = $root . '/.runtime';
if (!is_dir($backupDir) && !mkdir($backupDir,0755,true)) throw new RuntimeException('Não foi possível criar a pasta do backup.');
$backup = $backupDir . '/service-images-before-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json';
$snapshot = json_encode($db->query('SELECT * FROM service_images ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
if (file_put_contents($backup,$snapshot,LOCK_EX)!==strlen($snapshot)) throw new RuntimeException('Falha no backup; instalação cancelada.');
$copied = [];
$result = [];
try {
    $db->beginTransaction();
    foreach ($plan as $image) {
        $lock=$db->prepare('SELECT id FROM services WHERE id=? AND active=1 FOR UPDATE');
        $lock->execute([$image['service_id']]);
        if (!$lock->fetchColumn()) throw new RuntimeException('O serviço foi removido durante a instalação.');
        $target=$root.'/'.$image['path'];
        if (is_file($target)) {
            if (hash_file('sha256',$target)!==hash_file('sha256',$image['source'])) throw new RuntimeException('Arquivo existente diferente; não será substituído.');
        } else {
            if (!copy($image['source'],$target)) throw new RuntimeException('Falha ao copiar imagem.');
            $copied[]=$target;
        }
        $existing=$db->prepare('SELECT id FROM service_images WHERE services_id=? AND path=?');
        $existing->execute([$image['service_id'],$image['path']]);
        if ($existing->fetchColumn()) { $result[]=['service'=>$image['name'],'status'=>'já instalada']; continue; }
        $count=$db->prepare('SELECT COUNT(*) FROM service_images WHERE services_id=?');
        $count->execute([$image['service_id']]);
        if ((int)$count->fetchColumn()>=10) throw new RuntimeException('Limite de imagens atingido para '.$image['name']);
        $db->prepare('UPDATE service_images SET cover=0 WHERE services_id=?')->execute([$image['service_id']]);
        $db->prepare('INSERT INTO service_images (services_id,path,caption,cover) VALUES (?,?,?,1)')->execute([$image['service_id'],$image['path'],$image['caption']]);
        $result[]=['service'=>$image['name'],'status'=>'capa cadastrada'];
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    foreach ($copied as $target) {
        $real=realpath($target); $base=realpath($destination);
        if ($real && $base && str_starts_with($real,$base.DIRECTORY_SEPARATOR)) unlink($real);
    }
    throw $error;
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\nBackup: $backup\n";
