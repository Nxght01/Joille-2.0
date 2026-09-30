<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>..:: Login Administrativo ::..</title>
    <link rel="stylesheet" href="<?= url("views/assets/_common/styles/states.css"); ?>">
    <link rel="stylesheet" href="<?= url("views/assets/admin/styles/styles.css"); ?>">
    <script type="module" src="<?= url("views/assets/admin/scripts/login.js"); ?>"></script>
</head>
<body>
<div class="login-container">
    <h2>Login Administrativo</h2>

    <form id="formAdmin" method="POST">
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
        <a href="<?= url("esqueci-senha"); ?>">Esqueci minha senha</a>
    </form>

    <!-- Contêiner para as mensagens toast -->
    <div class="toast-container"></div>
</div>
</body>
</html>
