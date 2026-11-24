<?php
$navigation = [
    'profile' => ['app/', 'Meu perfil'],
    'services' => ['app/servicos', 'Serviços'],
    'requests' => ['app/solicitacoes', 'Meus projetos'],
    'password' => ['app/redefinir-senha', 'Segurança'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require __DIR__ . '/../shared/module-versions.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($pageTitle ?? 'Minha conta | Joille'); ?></title>
    <link rel="stylesheet" href="<?= url('views/assets/_common/styles/states.css'); ?>">
    <script type="module" src="<?= url('views/assets/app/scripts/_theme.js'); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/app/scripts/_theme.js'); ?>"></script>
    <?= $this->section('specific-script'); ?>
    <link rel="stylesheet" href="<?= url('views/assets/app/styles/styles.css'); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/app/styles/styles.css'); ?>">
</head>
<body class="private-area">
    <a class="skip-link" href="#main-content">Pular para o conteúdo</a>
    <header class="app-header">
        <div class="app-header-inner">
            <a class="app-brand" href="<?= url(); ?>" aria-label="Joille, página inicial">joille</a>
            <span class="app-area-label">Área do cliente</span>
            <div id="user-greeting">
                <img id="imageUser" data-profile-photo src="<?= url('views/assets/public/images/avatar-default.svg'); ?>" alt="Sua foto de perfil" width="44" height="44">
                <span id="span-user">Minha conta</span>
            </div>
            <button type="button" id="logout" class="app-logout">Sair</button>
        </div>
    </header>
    <nav class="app-navigation" aria-label="Área do cliente">
        <div id="private-navbar">
            <?php foreach ($navigation as $key => [$path, $label]): ?>
                <a href="<?= url($path); ?>"<?= ($activePage ?? '') === $key ? ' aria-current="page"' : ''; ?>><?= $label; ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
    <main id="main-content" class="content-private" tabindex="-1">
        <?= $this->section('content'); ?>
    </main>
    <div id="toast-container" role="status" aria-live="polite"></div>
    <footer class="private-footer">
        <span>© <?= date('Y'); ?> Joille</span>
        <a href="<?= url('contato'); ?>">Precisa de ajuda? Fale conosco</a>
    </footer>
</body>
</html>
