<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/app/scripts/password.js"); ?>"></script>
<?php $this->end(); ?>

<div class="private-area">
    <h1>Redefinir Senha</h1>

    <form id="form-password">
        <div class="form-group">
            <label for="password">Senha atual:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="newPassword">Nova senha:</label>
            <input type="password" id="newPassword" name="newPassword" minlength="8" required>
        </div>
        <div class="form-group">
            <label for="confirmNewPassword">Confirme a nova senha:</label>
            <input type="password" id="confirmNewPassword" name="confirmNewPassword" minlength="8" required>
        </div>
        <button type="submit">Atualizar Senha</button>
    </form>

    <!-- Contêiner para as mensagens toast -->
    <div class="toast-container"></div>
</div>
