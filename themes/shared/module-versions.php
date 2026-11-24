<?php
// Versiona também dependências dos módulos: atualizar só o script de entrada
// não invalida uma entidade que o navegador já tenha guardado em cache.
$imports = [];
foreach (['entities/Service', 'entities/ServiceRequest', 'services/ServiceService', 'services/RequestService', 'services/ContactService'] as $module) {
    $path = 'views/assets/_common/classes/' . $module . '.js';
    $imports[url($path)] = url($path) . '?v=' . filemtime(dirname(__DIR__, 2) . '/' . $path);
}
foreach (['views/assets/_common/prototypes/CrudController.js'] as $path) {
    $imports[url($path)] = url($path) . '?v=' . filemtime(dirname(__DIR__, 2) . '/' . $path);
}
?>
<script type="importmap"><?= json_encode(['imports' => $imports], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
