<?php
    $this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/cadastro.css"); ?>">
<script type="module" src="<?= url("views/assets/public/scripts/cadastro.js"); ?>"></script>
<?php $this->end(); ?>

<div class="register-container">
    <h2>Cadastro</h2>

    <form id="formRegister" method="POST">
        <div class="form-group">
            <label for="name">Nome:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" minlength="8" required>
        </div>
        <button type="submit">Cadastre-se</button>
        <hr>
        <a href="<?= url("login"); ?>"><span>Tem cadastro? faça seu login aqui</span></a>
    </form>

    <div class="toast-container"></div>
</div>
