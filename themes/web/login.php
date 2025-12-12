<?php
    $this->layout("_theme");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="assets/css/web/login.css">
  <script type="module" src="<?= url("assets/js/web/login.js"); ?>" async></script>
</head>
<body>
  <div class="login-container">
    <h2>Login</h2>
    <form id="formLogin" action="#" method="POST">
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="password">Senha:</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit">Entrar</button>
      <a href="<?= url("cadastro") ?>"><span>Não tem uma conta? Cadastre-se aqui.</span></a>
    </form>
  </div>
</body>
</html>



