<?php
    $this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/services.css"); ?>">
<link rel="stylesheet" href="<?= url("views/assets/public/styles/agency.css"); ?>">
<link rel="stylesheet" href="<?= url("views/assets/_common/styles/gallery.css"); ?>">
<script src="https://kit.fontawesome.com/10c90aa659.js" crossorigin="anonymous"></script>
<script type="module" src="<?= url("views/assets/public/scripts/services.js"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/public/scripts/services.js'); ?>"></script>
<?php $this->end(); ?>

<section class="services-section">
    <div class="container">
        <h1 class="section-title">Tecnologia para empresas joalheiras</h1>

        <p>Sistemas de gestão, infraestrutura de redes e integração de máquinas. Cada projeto recebe uma análise técnica e um orçamento próprio.</p>
        <div class="services-filter">
            <label for="filterCategories">Filtrar por categoria:</label>
            <select id="filterCategories">
                <option value="all">Todas</option>
            </select>
        </div>

        <div class="services-list">
            <!-- Serviços carregados da API -->
        </div>
    </div>
</section>

<!-- Galeria de fotos de um serviço, carregada sob demanda -->
<section id="serviceModal" class="modal">
    <article class="modal-content">
        <h2 id="serviceModalTitle">Serviço</h2>
        <p id="serviceModalDescription"></p>
        <strong id="serviceModalValue"></strong>

        <div class="gallery-grid" id="serviceModalGallery"></div>

        <div class="modal-actions">
            <button type="button" class="btn btn-cancel">Fechar</button>
        </div>
    </article>
</section>
