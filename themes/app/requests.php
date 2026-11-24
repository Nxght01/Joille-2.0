<?php $this->layout('_theme', ['activePage' => 'requests', 'pageTitle' => 'Meus projetos | Joille']); ?>
<?php $this->start('specific-script'); ?>
<link rel="stylesheet" href="<?= url('views/assets/_common/styles/requests.css'); ?>">
<script type="module" src="<?= url('views/assets/_common/scripts/requests.js'); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/_common/scripts/requests.js'); ?>"></script>
<?php $this->end(); ?>
<?php $admin = false; require __DIR__ . '/../shared/requests.php'; ?>
