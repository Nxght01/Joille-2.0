<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>..:: Painel de Administração ::..</title>
    <link rel="stylesheet" href="<?= url("views/assets/_common/styles/states.css"); ?>">
    <link rel="stylesheet" href="<?= url("views/assets/admin/styles/_theme.css"); ?>">
    <script type="module" src="<?= url("views/assets/admin/scripts/_theme.js"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/admin/scripts/_theme.js'); ?>"></script>
    <?php if ($this->section("specific-style")): ?>
        <?= $this->section("specific-style"); ?>
    <?php endif; ?>
    <?php if ($this->section("specific-script")): ?>
        <?= $this->section("specific-script"); ?>
    <?php endif; ?>
</head>
<body>
<header>
    <h1>Painel de Administração</h1>
    <div class="admin-greeting">
        <img id="imageAdmin" data-profile-photo src="<?= url("views/assets/public/images/avatar-default.svg"); ?>" alt="Foto do administrador" width="50" height="50">
        <span id="span-admin"></span>
    </div>
</header>

<nav>
    <a href="<?= url("admin/dashboard"); ?>">Dashboard</a>
    <a href="<?= url('admin/perfil'); ?>">Meu perfil</a>
    <a href="<?= url('admin/solicitacoes'); ?>">Solicitações</a>
    <a href="<?= url("admin/editar-usuario"); ?>">Gerenciar Usuários</a>
    <a href="<?= url("admin/editar-servicos"); ?>">Gerenciar Serviços</a>
    <a href="<?= url("admin/editar-categoria"); ?>">Gerenciar Categorias</a>
    <a href="<?= url("admin/editar-faqs"); ?>">Gerenciar FAQs</a>
    <a href="<?= url("admin/mensagens"); ?>">Mensagens</a>
    <a href="#" id="logout">Sair</a>
</nav>

<!-- Contêiner para as mensagens toast -->
<div id="toast-container"></div>

<main>
    <?= $this->section("content"); ?>
</main>
</body>
</html>
