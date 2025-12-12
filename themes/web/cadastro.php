<?php
    $this->layout("_theme");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <link rel="stylesheet" href="assets/css/web/cadastro.css">
  <script type="module" src="<?= url("assets/js/web/cadastro.js"); ?>" async></script>
</head>
<body>
  <div class="register-container">
    <h2>Cadastro</h2>
    <form id="formRegister" action="#" method="POST">
      <div class="form-group">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="name" >
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="text" id="email" name="email" >
      </div>
      <div class="form-group">
        <label for="password">Senha:</label>
        <input type="password" id="password" name="password" >
      </div>
      <button type="submit">Cadastre-se</button>
      <a href="<?= url("login")?>"><span>tem cadastro? faça seu login aqui</span></a>
    </form>
  </div>
</body>
</html>
