<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/login.css"); ?>">
<script type="module" src="<?= url("views/assets/public/scripts/reset_password.js"); ?>"></script>
<?php $this->end(); ?>

<div class="login-container">
    <h2>Criar nova senha</h2>

    <!-- Enquanto a API não confirma o token, nem o formulário nem o erro aparecem -->
    <p class="form-hint" id="checking">Verificando o link de recuperação...</p>

    <p class="list-state list-state-error" id="invalidLink" hidden></p>

    <form id="formReset" method="POST" hidden>
        <p class="form-hint">
            Criando uma senha nova para <strong id="accountEmail"></strong>.
        </p>

        <div class="form-group">
            <label for="password">Nova senha:</label>
            <input type="password" id="password" name="password" required
                   minlength="8" autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="confirmPassword">Confirme a nova senha:</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required
                   minlength="8" autocomplete="new-password">
        </div>

        <button type="submit">Salvar nova senha</button>

        <hr>
        <a href="<?= url("login"); ?>"><span>Voltar para o login</span></a>
    </form>

    <div class="toast-container"></div>
</div>
