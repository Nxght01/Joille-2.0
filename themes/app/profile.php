<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<script type="module" src="<?= url("views/assets/app/scripts/profile.js"); ?>"></script>
<?php $this->end(); ?>

<!-- Formulário para alteração do Perfil do Usuário: Nome, E-mail e Foto -->

<div class="private-area">
    <h1>Perfil do Usuário</h1>

    <form id="profile">
        <div class="form-group">
            <label for="name">Nome:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <button type="submit">Atualizar Perfil</button>
    </form>

    <form id="form-photo">
        <div class="form-group">
            <label for="photo">Foto:</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png" aria-describedby="photo-help">
            <small id="photo-help">JPG ou PNG, até 4 MB.</small>
            <div id="photoPreview" aria-live="polite"></div>
            <button type="submit">Atualizar Foto</button>
        </div>
    </form>

    <!-- Contêiner para as mensagens toast -->
    <div class="toast-container"></div>
</div>
