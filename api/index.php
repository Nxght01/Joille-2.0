<?php

ob_start();

require  __DIR__ . "/../vendor/autoload.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

use CoffeeCode\Router\Router;

$route = new Router(url(),":");

$route->namespace("Source\App\Api");

// Users routes
$route->group("/users");
$route->get("/", "Users:listUsers");
$route->post("/login", "Users:loginUser");
$route->post("/","Users:createUser");
$route->put("/update","Users:updateProfile");
$route->post("/set-password","Users:setPassword");
$route->get("/token-validate", "Users:tokenValidate");
$route->get("/user", "Users:getUser");
$route->post("/users/profile", "Users:updateProfile");
$route->post("/users/photo", "Users:updatePhoto");
$route->group("null");

// Services routes
$route->group("/services");
// Added new route for base services page
$route->get("/", "Services:index"); // New route to handle base services page
$route->get("/list","Services:listService");
$route->get("/list/{id}","Services:listById");
$route->post("/insert-service","Services:insertServices");
$route->post("/update-service/{id}","Services:updateService");
$route->delete("/delete/{id}","Services:delete");
$route->group("null");

// Categories routes
$route->group("/categories");
$route->get("/list","Categories:listCategory");
$route->get("/list/{id}","Categories:listById");
$route->post("/insert-category","Categories:insertCategories");
$route->post("/update-category/{id}","Categories:updateCategory");
$route->delete("/delete-category/{id}","Categories:deleteCategory");
$route->group("null");

// Admin routes
$route->group("/admin");
$route->post("/","Admins:insert");
$route->group("null");

$route->dispatch();

/** ERROR REDIRECT */
if ($route->error()) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(404);

    echo json_encode([
        "errors" => [
            "type " => "endpoint_not_found",
            "message" => "Não foi possível processar a requisição"
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

ob_end_flush();