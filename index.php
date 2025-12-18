<?php

require __DIR__ . "/vendor/autoload.php";

use CoffeeCode\Router\Router;

ob_start();

$route = new Router(url(), ":");

$route->namespace("Source\App");
// Rotas amigáveis da área pública
$route->get("/", "Web:home");
$route->get("/sobre", "Web:about");
$route->get("/contato", "Web:contact");
$route->get("/localizacao", "Web:location");
$route->get("/carrinho-compras","Web:cart");
$route->get("/services","Web:services");
$route->get("/faqs","Web:faqs");
$route->get("/login","Web:login");
$route->get("/cadastro","Web:cadastro");

// Rotas amigáveis da área restrita
$route->group("/app");

$route->get("/", "App:profile");
$route->get("/servicos", "App:services");
$route->get("/redefinir-senha", "App:password");

//$route->get("/carrinho", "App:cart");

$route->group(null);

$route->group("/admin");

$route->get("/", "Admin:home");
$route->get("/cadastro-produtos", "Admin:products");
$route->get("/editar-usuario", "Admin:editUser");
$route->get("/editar-serviços", "Admin:editServices");
$route->get("/editar-categoria", "Admin:editCategory");
$route->get("/editar-pedidos", "Admin:editOrders");

$route->group(null);

$route->get("/ops/{errcode}", "Web:error");

$route->group(null);

$route->dispatch();

if ($route->error()) {
    $route->redirect("/ops/{$route->error()}");
}

ob_end_flush();