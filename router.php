<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if ($path === '/') {
    require __DIR__ . '/index.html';
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo '404 - Arquivo nao encontrado';
