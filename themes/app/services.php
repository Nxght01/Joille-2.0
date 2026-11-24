<?php
$this->layout("_theme", ['activePage' => 'services', 'pageTitle' => 'Serviços | Joille']);
?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/app/scripts/services.js"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/app/scripts/services.js'); ?>"></script>
<?php $this->end(); ?>

<section class="services-page">
    <header class="app-page-header">
        <h1>Serviços</h1>
        <p>Sistemas, redes e integração de máquinas para empresas joalheiras. Escolha uma solução e envie o briefing para receber um orçamento.</p>
    </header>

    <section class="services-list">
        <div class="list-header" aria-hidden="true">
            <span>Serviço</span>
            <span>Descrição</span>
            <span>Categoria</span>
            <span>Investimento</span>
            <span>Projeto</span>
        </div>
        <div class="services-container" aria-live="polite">
            <!-- Serviços inseridos dinamicamente aqui -->
        </div>
    </section>
</section>
