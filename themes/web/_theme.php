<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <!--
        Sem esta linha o celular renderiza a página com largura de desktop
        (~980px) e depois reduz tudo: o texto fica minúsculo e nenhum
        `@media (max-width: 768px)` das folhas chega a valer. Os temas da área
        do cliente e do painel já a tinham; só a área pública estava sem.
    -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>..:: Joille ::..</title>

    <link rel="stylesheet" href="<?= url("views/assets/_common/styles/states.css"); ?>">
    <link rel="stylesheet" href="<?= url("views/assets/public/styles/_theme.css"); ?>">
    <script type="module" src="<?= url("views/assets/public/scripts/_theme.js"); ?>"></script>

    <?php if ($this->section("specific-script")): ?>
        <?= $this->section("specific-script"); ?>
    <?php endif; ?>
</head>

<body>

    <header>
        <div class="header-image">
            <a href="<?= url(); ?>">
                <img src="<?= url("views/assets/public/images/joille.png"); ?>" alt="Logo Joille" class="header-logo">
            </a>
        </div>

        <nav class="header-nav">
            <ul class="header-nav-list">
                <li><a href="<?= url(); ?>">Início</a></li>
                <li><a href="<?= url("sobre"); ?>">Sobre</a></li>
                <li><a href="<?= url("services"); ?>">Serviços</a></li>
                <li><a href="<?= url("faqs"); ?>">FAQs</a></li>
                <li><a href="<?= url("contato"); ?>">Contato</a></li>
                <li><a href="<?= url("localizacao"); ?>">Localização</a></li>

                <li data-auth-guest><a href="<?= url("login"); ?>">Login</a></li>
                <li data-auth-guest><a href="<?= url("cadastro"); ?>">Cadastro</a></li>

                <li data-auth-user hidden><span id="nav-greeting"></span></li>
                <li data-auth-user hidden><a href="<?= url("app/"); ?>">Minha Conta</a></li>
                <li data-auth-user hidden><a href="#" id="nav-logout">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <?= $this->section("content"); ?>
    </main>

    <!-- Contêiner para as mensagens toast -->
    <div class="toast-container"></div>

    <footer>
        <p>&copy; 2024 — Joille • Fornecedor de Serviços. Todos os direitos reservados.</p>
    </footer>

</body>
</html>
