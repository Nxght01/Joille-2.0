<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/error.css"); ?>">
<?php $this->end(); ?>

<section class="error-page">
    <strong class="error-code"><?= $this->e((string)$code); ?></strong>
    <h1><?= $this->e($title); ?></h1>
    <p><?= $this->e($message); ?></p>

    <nav class="error-links">
        <a href="<?= url(); ?>">Voltar para a página inicial</a>
        <a href="<?= url("contato"); ?>">Falar com a gente</a>
    </nav>
</section>
