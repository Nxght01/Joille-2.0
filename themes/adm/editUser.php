<?php
$this->layout("_theme");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="<?= url('assets/css/adm/editUser.css'); ?>">
    <script type="module" src="<?= url('assets/js/adm/edit_user.js'); ?>" async></script>
</head>
<body>
<header>
    <h1>Administração de Usuários</h1>
    <section class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Buscar pelo nome ou email do usuário...">
    </section>
</header>

<!-- Contêiner para as mensagens toast -->
<div id="toast-container"></div>

<main>
    <section class="users-list">
        <header class="list-header">
            <span>ID</span>
            <span>Nome</span>
            <span>Email</span>
            <span>Ações</span>
        </header>
        <section class="users-container" id="usersList">
            <!-- Users will be dynamically populated here -->
        </section>
    </section>
</main>

<section id="editModal" class="modal">
    <article class="modal-content">
        <h2>Edição de Usuário</h2>
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
                <label for="address">Endereço</label>
                <input type="text" id="address" name="address" class="form-control">
            </div>

            <div class="form-group">
                <label for="role">Papel</label>
                <select id="role" name="role" class="form-control">
                    <option value="user">Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-save">Salvar</button>
                <button type="button" class="btn btn-cancel">Cancelar</button>
            </div>
        </form>
    </article>
</section>
</body>
</html>