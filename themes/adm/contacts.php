<?php
$this->layout("_theme");
?>

<?php $this->start("specific-style"); ?>
<link rel="stylesheet" href="<?= url("views/assets/admin/styles/crud.css"); ?>">
<link rel="stylesheet" href="<?= url("views/assets/admin/styles/contacts.css"); ?>">
<?php $this->end(); ?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/admin/scripts/contacts.js"); ?>"></script>
<?php $this->end(); ?>

<header>
    <h1>Mensagens de Contato</h1>
    <section class="toolbar">
        <input type="text" id="searchInput" class="search-input" placeholder="Buscar por nome, e-mail ou assunto...">
        <select name="searchStatus" id="searchStatus">
            <option value="all">Todas</option>
            <option value="pending">Pendentes</option>
            <option value="answered">Respondidas</option>
        </select>
    </section>
</header>

<section class="services-list">
    <header class="list-header list-header-contacts">
        <span>Recebida</span>
        <span>De</span>
        <span>Assunto</span>
        <span>Situação</span>
        <span>Ações</span>
    </header>
    <section class="services-container" id="contactsList">
        <!-- Mensagens inseridas dinamicamente aqui -->
    </section>
</section>

<section id="viewModal" class="modal">
    <article class="modal-content">
        <h2 id="modalTitle">Mensagem</h2>

        <dl class="message-details">
            <dt>De</dt>
            <dd id="detailFrom">-</dd>
            <dt>E-mail</dt>
            <dd><a id="detailEmail" href="#">-</a></dd>
            <dt>Recebida em</dt>
            <dd id="detailDate">-</dd>
            <dt>Assunto</dt>
            <dd id="detailSubject">-</dd>
        </dl>

        <p class="message-body" id="detailMessage"></p>

        <div class="modal-actions">
            <button type="button" class="btn btn-save" id="btnAnswer">Marcar como respondida</button>
            <button type="button" class="btn btn-cancel">Fechar</button>
        </div>
    </article>
</section>
