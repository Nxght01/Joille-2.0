<?php
$this->layout("_theme");
?>

<?php $this->start("specific-style"); ?>
<link rel="stylesheet" href="<?= url("views/assets/admin/styles/crud.css"); ?>">
<?php $this->end(); ?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/admin/scripts/edit_user.js"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/admin/scripts/edit_user.js'); ?>"></script>
<?php $this->end(); ?>

<header>
    <h1>Administração de Usuários</h1>
    <section class="toolbar">
        <input type="text" id="searchInput" class="search-input" placeholder="Buscar pelo nome ou email do usuário...">
        <button type="button" class="btn btn-new" id="btnNew">+ Novo Administrador</button>
    </section>
</header>

<section class="users-list services-list">
    <header class="list-header list-header-users">
        <span>ID</span>
        <span>Nome</span>
        <span>Email</span>
        <span>Perfil</span>
        <span>Ações</span>
    </header>
    <section class="users-container services-container" id="usersList">
        <!-- Usuários inseridos dinamicamente aqui -->
    </section>
</section>

<section id="editModal" class="modal">
    <article class="modal-content">
        <h2 id="modalTitle">Edição de Usuário</h2>
        <form id="editForm">
            <input type="hidden" id="id" name="id">

            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" class="form-control" minlength="8"
                       placeholder="Obrigatória somente ao criar um novo administrador">
            </div>

            <div class="form-group">
                <label for="type_id">Perfil</label>
                <select id="type_id" name="type_id" class="form-control">
                    <option value="2">Cliente</option>
                    <option value="1">Administrador</option>
                </select>
            </div>

            <div class="form-group">
                <label for="active">Situação</label>
                <select id="active" name="active" class="form-control">
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-save">Salvar</button>
                <button type="button" class="btn btn-cancel">Cancelar</button>
            </div>
        </form>
    </article>
</section>
