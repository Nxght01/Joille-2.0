<?php
// Roteador de desenvolvimento para php -S; não expõe código, dumps ou configurações.
$root = dirname(__DIR__, 2);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = realpath($root . $path);
if ($file && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)
    && preg_match('~^/(views/assets/|uploads/)~', $path)
    && preg_match('/\.(css|js|svg|png|jpe?g|webp|gif|woff2?)$/i', $file)) {
    return false;
}
if (preg_match('~^/(source|vendor|tests|storage|\.runtime|\.git|banco de dados)~i', $path)) {
    http_response_code(404);
    exit;
}
chdir($root);
require $root . (preg_match('~^/api(?:/|$)~', $path) ? '/api/index.php' : '/index.php');
