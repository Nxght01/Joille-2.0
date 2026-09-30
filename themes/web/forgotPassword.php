<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/login.css"); ?>">
<script type="module" src="<?= url("views/assets/public/scripts/forgot_password.js"); ?>"></script>
<?php $this->end(); ?>

<div class="login-container">
    <h2>Esqueci minha senha</h2>

    <p class="form-hint">
        Informe o e-mail cadastrado. Enviaremos um link para você criar uma senha
        nova — ele vale por <?= CONF_PASSWORD_RESET_EXPIRE; ?> minutos.
    </p>

    <?php if (CONF_MAIL_MODE === "capture"): ?>
        <!-- Aviso exibido só no modo de teste; em "smtp" o bloco não é renderizado -->
        <p class="dev-notice">
            <strong>Modo de teste.</strong> Nenhum e-mail sai para a internet.
            Depois de enviar, abra a
            <a href="<?= url(CONF_MAIL_CAPTURE_DIR . "/"); ?>" target="_blank" rel="noopener">caixa
            de entrada local</a> para clicar no link de recuperação.
        </p>
    <?php endif; ?>

    <form id="formForgot" method="POST">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required autocomplete="email">
        </div>

        <button type="submit">Enviar link de recuperação</button>

        <hr>
        <a href="<?= url("login"); ?>"><span>Voltar para o login</span></a>
    </form>

    <div class="toast-container"></div>
</div>
