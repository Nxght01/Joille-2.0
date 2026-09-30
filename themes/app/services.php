<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/app/scripts/services.js"); ?>"></script>
<?php $this->end(); ?>

<div class="private-area">
    <h1>Serviços</h1>

    <section class="services-list">
        <header class="list-header">
            <span>Nome</span>
            <span>Descrição</span>
            <span>Categoria</span>
            <span>Valor</span>
        </header>
        <section class="services-container">
            <!-- Serviços inseridos dinamicamente aqui -->
        </section>
    </section>
</div>
