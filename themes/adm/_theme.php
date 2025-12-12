<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= url("assets/css/adm/_theme.css"); ?>">
</head>
<body>
<header>
    <h1>Painel de Administração</h1>
</header>

<nav>
    <a href="<?= url("admin/"); ?>">Dashboard</a>
    <a href="<?= url("admin/editar-usuario"); ?>">Gerenciar Usuários</a>
    <a href="<?= url("admin/editar-serviços"); ?>">Gerenciar Serviços</a>
    
    
    <a href="#sair">Sair</a>
</nav>
</body>
</html>

<?php

echo $this->section("content");


