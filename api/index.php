<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
// timezone para São Paulo América
date_default_timezone_set('America/Sao_Paulo');

ob_start();

require  __DIR__ . "/../vendor/autoload.php";
\Source\Support\ApiErrors::register();

// os headers abaixo são necessários para permitir o acesso à API por clientes externos ao domínio
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, token");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

use Source\Core\Router;

$route = new Router(url("api"),":");

$route->namespace("Source\Controller");

// ATENÇÃO — ORDEM DAS ROTAS
// O dispatch do CoffeeCode percorre TODAS as rotas do método HTTP e mantém a
// ÚLTIMA que casar (não há break). Por isso as rotas com curinga vêm primeiro e
// as literais depois: "/list" precisa ser registrada DEPOIS de "/{serviceId}",
// senão a rota curinga venceria e "list" chegaria como id.

$route->group("/users");
$route->post("/register","Users:register"); // Registrar usuário comum
$route->post("/login","Users:auth"); // login de usuário comum
$route->post("/register-admin","Users:registerAdmin"); // Registrar usuário admin
$route->post("/login-admin","Users:authAdmin"); // login de usuário admin
$route->get("/token-validate","Users:tokenValidate"); // valida o token do usuário logado
$route->get("/me","Users:me"); // dados do usuário autenticado
$route->put("/update","Users:update"); // update do usuário autenticado
$route->put("/password","Users:updatePassword"); // troca de senha do usuário autenticado
$route->post("/photo","Users:updatePhoto"); // troca de foto do usuário autenticado
// "/admin/{userId}" precisa vir antes de "/admin/list": o dispatch do CoffeeCode percorre
// todas as rotas e mantém a ÚLTIMA que casar, então a rota literal tem de ser registrada depois
$route->get("/admin/{userId}","Users:listById"); // select by id (admin)
$route->get("/admin/list","Users:listAll"); // listagem administrativa
$route->put("/admin/{userId}","Users:updateAdmin"); // update de usuário pelo admin
$route->delete("/admin/{userId}","Users:delete"); // soft delete pelo admin
$route->group(null);

// Serviços — ENTIDADE PRINCIPAL do sistema
$route->group("/services");
$route->get("/","Services:listAll"); // select all ativos
$route->get("/{serviceId}","Services:listById"); // select by id (atalho)
// Rotas no formato pedido no enunciado da Etapa 03
$route->get("/list","Services:listAll"); // select all
$route->get("/list/{serviceId}","Services:listById"); // select by id
$route->get("/list/paginator/{page}/{per_page}","Services:listPaginator"); // select all paginado
$route->get("/paginator/{page}/{per_page}","Services:listPaginator"); // idem (atalho)
$route->get("/category/{categoryId}","Services:listByCategory"); // select by categoria
$route->get("/admin/list","Services:listAllAdmin"); // select all incluindo inativos (admin)
$route->post("/","Services:insert"); // insert
$route->put("/{serviceId}","Services:update"); // update
$route->delete("/{serviceId}","Services:delete"); // soft delete
$route->group(null);

// Imagens da entidade principal (N imagens por serviço)
$route->group("/service-images");
$route->get("/{serviceId}","ServiceImages:listByService"); // imagens de um serviço (público)
$route->post("/{serviceId}","ServiceImages:insert"); // upload de 1..N imagens (admin)
$route->put("/{serviceId}/cover/{imageId}","ServiceImages:setCover"); // define a capa (admin)
$route->delete("/{serviceId}/{imageId}","ServiceImages:delete"); // remove imagem (admin)
$route->group(null);

// Categorias de Serviços — SEGUNDA ENTIDADE com o conjunto completo de rotas
$route->group("/categories");
$route->get("/","Categories:listAll");
$route->get("/{categoryId}","Categories:listById");
// Rotas no formato pedido no enunciado da Etapa 03
$route->get("/list","Categories:listAll");
$route->get("/list/{categoryId}","Categories:listById");
$route->get("/list/paginator/{page}/{per_page}","Categories:listPaginator");
$route->get("/admin/list","Categories:listAllAdmin");
$route->post("/","Categories:insert");
$route->put("/{categoryId}","Categories:update");
$route->delete("/{categoryId}","Categories:delete");
$route->group(null);

// FAQs
$route->group("/faqs");
$route->get("/","Faqs:listAll");
$route->get("/admin/list","Faqs:listAllAdmin");
$route->get("/type/{typeId}","Faqs:listByType");
$route->get("/{faqId}","Faqs:listById");
$route->post("/","Faqs:insert");
$route->put("/{faqId}","Faqs:update");
$route->delete("/{faqId}","Faqs:delete");
$route->group(null);

// Tipos de FAQs
$route->group("/faqs-types");
$route->get("/","FaqsTypes:listAll");
$route->get("/{typeId}","FaqsTypes:listById");
$route->post("/","FaqsTypes:insert");
$route->put("/{typeId}","FaqsTypes:update");
$route->delete("/{typeId}","FaqsTypes:delete");
$route->group(null);

// Recuperação de senha por e-mail (público)
$route->group("/passwords");
$route->post("/forgot","Passwords:forgot"); // envia o link de recuperação
$route->get("/validate/{token}","Passwords:validateToken"); // confere o link
$route->post("/reset","Passwords:reset"); // grava a nova senha
$route->group(null);

// Mensagens do formulário de contato
$route->group("/contacts");
$route->post("/","Contacts:insert"); // envio pelo site (público)
$route->get("/","Contacts:listAll"); // listagem (admin)
$route->get("/list","Contacts:listAll"); // idem
$route->put("/{contactId}","Contacts:update"); // marca como respondida (admin)
$route->delete("/{contactId}","Contacts:delete"); // exclui (admin)
$route->group(null);

$route->group('/requests');
$route->get('/', 'Requests:listAll');
$route->get('/{requestId}', 'Requests:listById');
$route->post('/', 'Requests:insert');
$route->put('/{requestId}', 'Requests:update');
$route->delete('/{requestId}', 'Requests:delete');
$route->group(null);

// Indicadores e relatórios (admin)
$route->group("/reports");
$route->get("/dashboard","Reports:dashboard"); // JSON com os números e séries dos gráficos
$route->get("/services/pdf","Reports:servicesPdf"); // relatório do catálogo em PDF
$route->get("/users/pdf","Reports:usersPdf"); // relatório de usuários em PDF
$route->get("/faqs/pdf","Reports:faqsPdf"); // relatório de FAQs em PDF
$route->group(null);

$route->dispatch();

/** ERROR REDIRECT */
if ($route->error()) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(404);

    echo json_encode([
        "code" => 404,
        "type" => "error",
        "status" => "not_found",
        "message" => "O recurso solicitado não existe.",
        "data" => null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

ob_end_flush();
