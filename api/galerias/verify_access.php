<?php
require_once __DIR__.'/../config.php';

$body  = body();
$token = $body['token'] ?? '';
$senha = $body['senha'] ?? '';

if (!$token || !$senha)
    json_out(['status'=>'erro','mensagem'=>'Token e senha obrigatórios.'], 400);

// Busca galeria pelo token
$stmt = db()->prepare("SELECT * FROM galerias WHERE link_token = ? LIMIT 1");
$stmt->execute([$token]);
$g = $stmt->fetch();
if (!$g) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

// Se galeria tem cliente vinculado, verifica senha_acesso
if ($g['cliente_id']) {
    $cli = db()->prepare("SELECT * FROM clientes WHERE id = ? LIMIT 1");
    $cli->execute([$g['cliente_id']]);
    $cliente = $cli->fetch();
    if (!$cliente || strtoupper($cliente['senha_acesso']) !== strtoupper(trim($senha)))
        json_out(['status'=>'erro','mensagem'=>'Senha incorreta.'], 401);
} elseif ($g['senha']) {
    // Galeria privada com senha hash (sem cliente vinculado)
    if (!password_verify($senha, $g['senha']))
        json_out(['status'=>'erro','mensagem'=>'Senha incorreta.'], 401);
} else {
    // Sem senha configurada — acesso livre
}

// Salva acesso na sessão
$_SESSION['galeria_access'][$g['id']] = true;

unset($g['senha']);
json_out([
    'status'    => 'ok',
    'galeria'   => $g,
    'dl_count'  => (int)($g['dl_count'] ?? 0),
    'dl_max'    => (int)($g['max_downloads'] ?? 0),
]);
