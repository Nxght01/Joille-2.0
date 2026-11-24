<?php $this->layout("web::_theme", ["pageTitle" => "Login administrativo | Joille"]); ?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/login.css"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/public/styles/login.css'); ?>">
<script type="module" src="<?= url("views/assets/admin/scripts/login.js"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/admin/scripts/login.js'); ?>"></script>
<?php $this->end(); ?>

<div class="login-container admin-login">
    <h1>Login administrativo</h1>
    <p class="form-hint" id="admin-login-hint">Entre com sua conta de administrador para acessar o painel Joille.</p>

    <form id="formAdmin" method="POST" aria-describedby="admin-login-hint">
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" autocomplete="username" required>
        </div>
        <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit">Entrar no painel</button>
        <hr>
        <a href="<?= url("esqueci-senha"); ?>">Esqueci minha senha</a>
        <a href="<?= url("login"); ?>">Acessar como cliente</a>
    </form>

    <div class="toast-container" role="status" aria-live="polite"></div>
</div>
