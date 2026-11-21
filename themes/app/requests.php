<?php $this->layout('_theme'); ?>
<?php $this->start('specific-script'); ?>
<link rel="stylesheet" href="<?= url('views/assets/_common/styles/requests.css'); ?>">
<script type="module" src="<?= url('views/assets/_common/scripts/requests.js'); ?>"></script>
<?php $this->end(); ?>
<?php $admin = false; require __DIR__ . '/../shared/requests.php'; ?>
