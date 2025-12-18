<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>..:: Meu Sistema ::..</title>

    <link rel="stylesheet" href="<?= url("assets/css/web/_theme.css"); ?>">
    <!-- <script type="module" src="<?= url("assets/js/web/_theme.js"); ?>" async></script> -->

    <?php if ($this->section("specific-script")): ?>
        <?= $this->section("specific-script"); ?>
    <?php endif; ?>
</head>

<body>

    <header>
        <div class="header-image">
            <a href="<?= url(); ?>">
                <img src="<?= url("assets/images/joille.png"); ?>" alt="Logo Joille" class="header-logo">
            </a>
        </div>

        <nav class="header-nav">
            <ul class="header-nav-list">
                <li><a href="<?= url(); ?>">Início</a></li>
                <li><a href="<?= url("sobre"); ?>">Sobre</a></li>
                <li><a href="<?= url("services"); ?>">Serviços</a></li>
                <li><a href="<?= url("faqs"); ?>">FAQs</a></li>
                <li><a href="<?= url("contato"); ?>">Contato</a></li>
                <li><a href="<?= url("login"); ?>">Login</a></li>
                <li><a href="<?= url("cadastro"); ?>">Cadastro</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <?= $this->section("content"); ?>
    </main>

    <footer>
        <p>&copy; 2024 — Joille • Fornecedor de Serviços. Todos os direitos reservados.</p>
    </footer>

</body>
</html>
