<?php
$this->layout("_theme");
?>

<?php $this->start("specific-style"); ?>
<link rel="stylesheet" href="<?= url("views/assets/admin/styles/crud.css"); ?>">
<link rel="stylesheet" href="<?= url("views/assets/_common/styles/gallery.css"); ?>">
<?php $this->end(); ?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/admin/scripts/edit_services.js"); ?>"></script>
<?php $this->end(); ?>

<header>
    <h1>Administração de Serviços</h1>
    <section class="toolbar">
        <input type="text" id="searchInput" class="search-input" placeholder="Buscar pelo nome do serviço...">
        <select name="searchCategories" id="searchCategories">
            <option value="all">Todas as categorias</option>
        </select>
        <button type="button" class="btn btn-new" id="btnNew">+ Novo Serviço</button>
    </section>
</header>

<section class="services-list">
    <header class="list-header list-header-services">
        <span>ID</span>
        <span>Capa</span>
        <span>Nome</span>
        <span>Categoria</span>
        <span>Imagens</span>
        <span>Ações</span>
    </header>
    <section class="services-container" id="servicesList">
        <!-- Serviços inseridos dinamicamente aqui -->
    </section>
</section>

<section id="editModal" class="modal">
    <article class="modal-content">
        <h2 id="modalTitle">Edição de Serviço</h2>
        <form id="editForm">
            <input type="hidden" id="id" name="id">
            <input type="hidden" id="active" name="active">

            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea id="description" name="description" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label for="value">Valor</label>
                <input type="text" id="value" name="value" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="categories_id">Categoria</label>
                <select id="categories_id" name="categories_id" class="form-control" required></select>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-save">Salvar</button>
                <button type="button" class="btn btn-cancel">Cancelar</button>
            </div>
        </form>

        <!--
            Imagens do serviço (entidade principal, N imagens).
            Só fica disponível na edição: o upload precisa de um id já gravado,
            então em "Novo Serviço" a seção aparece desabilitada.
        -->
        <section class="gallery-section" id="gallerySection">
            <h3>Imagens do serviço (<span id="galleryCount">0</span>/10)</h3>

            <p class="gallery-hint" id="galleryHint">
                Salve o serviço primeiro para poder enviar imagens.
            </p>

            <div class="gallery-upload">
                <input type="file" id="galleryInput" name="images[]"
                       accept="image/jpeg,image/png" multiple disabled>
                <button type="button" class="btn btn-save" id="galleryUpload" disabled>Enviar imagens</button>
            </div>

            <div class="gallery-grid" id="galleryGrid"></div>
        </section>
    </article>
</section>
