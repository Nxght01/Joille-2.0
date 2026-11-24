<?php
$this->layout("_theme");
?>

<?php $this->start("specific-style"); ?>
<link rel="stylesheet" href="<?= url("views/assets/admin/styles/crud.css"); ?>">
<?php $this->end(); ?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/admin/scripts/edit_category.js"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/admin/scripts/edit_category.js'); ?>"></script>
<?php $this->end(); ?>

<header>
    <h1>Administração de Categorias</h1>
    <section class="toolbar">
        <input type="text" id="searchInput" class="search-input" placeholder="Buscar pelo nome da categoria...">
        <button type="button" class="btn btn-new" id="btnNew">+ Nova Categoria</button>
    </section>
</header>

<section class="categories-list">
    <header class="list-header list-header-categories">
        <span>ID</span>
        <span>Nome</span>
        <span>Ações</span>
    </header>
    <section class="categories-container" id="categoriesList">
        <!-- Categorias inseridas dinamicamente aqui -->
    </section>
</section>

<section id="editModal" class="modal">
    <article class="modal-content">
        <h2 id="modalTitle">Edição de Categoria</h2>
        <form id="editForm">
            <input type="hidden" id="id" name="id">
            <input type="hidden" id="active" name="active">

            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-save">Salvar</button>
                <button type="button" class="btn btn-cancel">Cancelar</button>
            </div>
        </form>
    </article>
</section>
