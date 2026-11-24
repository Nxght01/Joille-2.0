<?php
$this->layout("_theme", ['activePage' => 'password', 'pageTitle' => 'Segurança | Joille']);
?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/app/scripts/password.js"); ?>"></script>
<link rel="stylesheet" href="<?= url('views/assets/_common/styles/requests.css'); ?>">
<?php $this->end(); ?>

<section class="password-page request-page" data-profile-scope="<?= $profileScope ?? 'user'; ?>">
    <header class="app-page-header">
        <h1>Segurança</h1>
        <p>Atualize sua senha para manter o acesso à sua conta protegido.</p>
    </header>

    <form id="form-password" class="request-form">
        <div class="form-heading"><h2>Alterar senha</h2><p id="password-help">Use uma senha com pelo menos 8 caracteres.</p></div>
        <div class="form-group">
            <label for="password">Senha atual:</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <div class="form-group">
            <label for="newPassword">Nova senha:</label>
            <input type="password" id="newPassword" name="newPassword" autocomplete="new-password" aria-describedby="password-help" minlength="8" required>
        </div>
        <div class="form-group">
            <label for="confirmNewPassword">Confirme a nova senha:</label>
            <input type="password" id="confirmNewPassword" name="confirmNewPassword" autocomplete="new-password" minlength="8" required>
        </div>
        <button type="submit">Salvar nova senha</button>
    </form>

    <!-- Contêiner para as mensagens toast -->
    <div class="toast-container"></div>
</section>
