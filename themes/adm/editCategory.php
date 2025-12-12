<?php
$this->layout("_theme");
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="<?= url("assets/css/adm/editCategory.css"); ?>">
    <script type="module" src="<?= url('assets/js/adm/edit_category.js'); ?>" async></script>
    </head>
<body>

<header>
    <h1>Editar Usuário</h1>
</header>

<div class="container">
    <section class="card">
        <h2>Dados do Usuário</h2>
        <form id="edit-form">
            <label for="userId">ID do Usuário:</label>
            <input type="text" id="userId" name="userId" required>
            <br>
            <label for="username">Nome de Usuário:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <br>
            <button type="submit">Salvar</button>
            <button type="button" id="cancel-btn">Cancelar</button>
        </form>
    </section>
</div>

<header>
    <h1>editar Serviço</h1>
</header>

<div class="container">
    <section class="card">
        <h2>editar dados do serviço</h2>
        <form id="edit-form">
            <label for="userId">ID do Usuário:</label>
            <input type="text" id="userId" name="userId" required>
            <br>
            <label for="username">Nome de Usuário:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="type">Tipo do Serviço:</label>
            <input type="text" id="type" name="type" required>
            <br>
            <label for="value">Valor Atribuído:</label>
            <input type="text" id="value" name="value" required>
            <br>
            <button type="submit" id="add-btn">Salvar</button>
            <button type="button" id="cancel-btn">Cancelar</button>
        </form>
    </section>
</div>

<header>
    <h1>deletar Serviço</h1>
</header>

<div class="container">
    <section class="card">
        <h2>deletar dados do serviço</h2>
        <form id="edit-form">
            <label for="userId">ID do Usuário:</label>
            <input type="text" id="userId" name="userId" required>
            <br>
            <label for="username">Nome de Usuário:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="type">Tipo do Serviço:</label>
            <input type="text" id="type" name="type" required>
            <br>
            <label for="value">Valor Atribuído:</label>
            <input type="text" id="value" name="value" required>
            <br>
            <button type="submit" id="add-btn">Salvar</button>
            <button type="button" id="cancel-btn">Cancelar</button>
        </form>
    </section>
</div>

<header>
    <h1>encontrar Serviço por ID</h1>
</header>

<div class="container">
    <section class="card">
        <h2>coloque os dados do serviço</h2>
        <form id="edit-form">
            <label for="userId">ID do Usuário:</label>
            <input type="text" id="userId" name="userId" required>
            <br>
            <label for="username">Nome de Usuário:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="type">Tipo do Serviço:</label>
            <input type="text" id="type" name="type" required>
            <br>
            <label for="value">Valor Atribuído:</label>
            <input type="text" id="value" name="value" required>
            <br>
            <button type="submit" id="add-btn">Salvar</button>
            <button type="button" id="cancel-btn">Cancelar</button>
        </form>
    </section>
</div>

</body>
</html>