<?php
$this->layout("_theme");
?>

<?php $this->start("specific-style"); ?>
<link rel="stylesheet" href="<?= url("views/assets/admin/styles/home.css"); ?>">
<?php $this->end(); ?>

<?php $this->start("specific-script"); ?>
<!-- Chart.js: geração dos gráficos do painel -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script type="module" src="<?= url("views/assets/admin/scripts/home.js"); ?>"></script>
<?php $this->end(); ?>

<div class="container">
    <section id="dashboard" class="card card-wide">
        <h2>Dashboard</h2>
        <p>Bem-vindo ao painel de administração. Aqui você acompanha os números do
           serviço, gera relatórios em PDF e gerencia todo o sistema.</p>
    </section>

    <section class="card">
        <h3>Serviços ativos</h3>
        <strong id="total-services">-</strong>
    </section>

    <section class="card">
        <h3>Categorias</h3>
        <strong id="total-categories">-</strong>
    </section>

    <section class="card">
        <h3>Usuários ativos</h3>
        <strong id="total-users">-</strong>
    </section>

    <section class="card">
        <h3>FAQs publicadas</h3>
        <strong id="total-faqs">-</strong>
    </section>

    <section class="card">
        <h3>Valor em catálogo</h3>
        <strong id="catalog-value">-</strong>
    </section>

    <section class="card">
        <h3>Mensagens pendentes</h3>
        <strong id="pending-contacts">-</strong>
        <a class="card-link" href="<?= url("admin/mensagens"); ?>">ver mensagens</a>
    </section>

    <section class="card card-wide">
        <h3>Relatórios em PDF</h3>
        <p class="card-hint">
            Consulte o catálogo, os usuários e as perguntas frequentes em PDF.
        </p>
        <div class="report-actions">
            <button type="button" class="btn btn-report" data-report="services">Catálogo de serviços</button>
            <button type="button" class="btn btn-report" data-report="users">Usuários cadastrados</button>
            <button type="button" class="btn btn-report" data-report="faqs">Perguntas frequentes</button>
        </div>
    </section>

    <section class="card chart-card">
        <h3>Serviços por categoria</h3>
        <div class="chart-holder"><canvas id="chart-services"></canvas></div>
    </section>

    <section class="card chart-card">
        <h3>Valor do catálogo por categoria</h3>
        <div class="chart-holder"><canvas id="chart-catalog"></canvas></div>
    </section>

    <section class="card chart-card">
        <h3>Usuários por perfil</h3>
        <div class="chart-holder"><canvas id="chart-users"></canvas></div>
    </section>

    <section class="card chart-card">
        <h3>FAQs por tipo</h3>
        <div class="chart-holder"><canvas id="chart-faqs"></canvas></div>
    </section>
</div>
