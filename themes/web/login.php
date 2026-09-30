<?php
    $this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/login.css"); ?>">
<script type="module" src="<?= url("views/assets/public/scripts/login.js"); ?>"></script>
<?php $this->end(); ?>

<div class="login-container">
    <h2>Login</h2>

    <form id="formLogin" method="POST">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Entrar</button>
        <hr>
        <a href="<?= url("esqueci-senha"); ?>"><span>Esqueci minha senha</span></a>
        <a href="<?= url("cadastro"); ?>"><span>Não tem uma conta? Cadastre-se aqui.</span></a>
    </form>

    <div class="toast-container"></div>
</div>
