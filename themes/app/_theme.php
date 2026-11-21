<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>..:: Área Privada ::..</title>
    <link rel="stylesheet" href="<?= url("views/assets/_common/styles/states.css"); ?>">
    <link rel="stylesheet" href="<?= url("views/assets/app/styles/styles.css"); ?>"> <!-- Atualize com o caminho correto do seu arquivo CSS -->
    <script type="module" src="<?= url("views/assets/app/scripts/_theme.js");  ?>"></script>
    <?php if ($this->section("specific-script")): ?>
        <?= $this->section("specific-script"); ?>
    <?php endif; ?>
</head>
<body class="private-area">
<header>
<nav id="private-navbar">
    <div id="user-greeting">
        <div id="user-photo">
            <img id="imageUser" data-profile-photo src="<?= url("views/assets/public/images/avatar-default.svg"); ?>" alt="Foto do usuário" width="50" height="50">
        </div>
        <span id="span-user">Olá, Fulano!</span>
    </div>
    <a href="<?= url("/app/"); ?>">Perfil</a>
    <a href="<?= url("/app/servicos"); ?>">Serviços</a>
    <a href="<?= url("/app/redefinir-senha"); ?>">Redefinir Senha</a>
    <a href="<?= url("/app/solicitacoes"); ?>">Minhas solicitações</a>
    <a id="logout">Sair</a>
</nav>
</header>
<!-- Contêiner para as mensagens toast -->
<div id="toast-container"></div>
<div class="content-private">
    <?php
    echo $this->section("content");
    ?>
</div>
<footer class="private-footer">
    © <?= date('Y'); ?> Joille. Todos os direitos reservados.
</footer>
</body>
</html>
