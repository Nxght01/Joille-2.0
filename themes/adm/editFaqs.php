<?php
$this->layout("_theme");
?>

<?php $this->start("specific-style"); ?>
<link rel="stylesheet" href="<?= url("views/assets/admin/styles/crud.css"); ?>">
<?php $this->end(); ?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/admin/scripts/edit_faq.js"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/admin/scripts/edit_faq.js'); ?>"></script>
<?php $this->end(); ?>

<header>
    <h1>Administração de Perguntas Frequentes</h1>
    <section class="toolbar">
        <input type="text" id="searchInput" class="search-input" placeholder="Buscar pela pergunta...">
        <select name="searchTypes" id="searchTypes">
            <option value="all">Todos os tipos</option>
        </select>
        <button type="button" class="btn btn-new" id="btnNew">+ Nova Pergunta</button>
    </section>
</header>

<section class="services-list">
    <header class="list-header list-header-faqs">
        <span>ID</span>
        <span>Pergunta</span>
        <span>Tipo</span>
        <span>Ações</span>
    </header>
    <section class="faqs-container services-container" id="faqsList">
        <!-- Perguntas inseridas dinamicamente aqui -->
    </section>
</section>

<section id="editModal" class="modal">
    <article class="modal-content">
        <h2 id="modalTitle">Edição de Pergunta</h2>
        <form id="editForm">
            <input type="hidden" id="id" name="id">
            <input type="hidden" id="active" name="active">

            <div class="form-group">
                <label for="question">Pergunta</label>
                <input type="text" id="question" name="question" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="answer">Resposta</label>
                <textarea id="answer" name="answer" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label for="types_id">Tipo</label>
                <select id="types_id" name="types_id" class="form-control" required></select>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-save">Salvar</button>
                <button type="button" class="btn btn-cancel">Cancelar</button>
            </div>
        </form>
    </article>
</section>
