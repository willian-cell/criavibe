<?php
require_once __DIR__.'/../config.php';

$body = body();
$email = strtolower(trim($body['email'] ?? ''));
$senha = $body['senha'] ?? '';

if (!$email || !$senha) json_out(['status'=>'erro','mensagem'=>'E-mail e senha obrigatórios.'], 400);

$stmt = db()->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$u = $stmt->fetch();

if (!$u || !password_verify($senha, $u['senha']))
    json_out(['status'=>'erro','mensagem'=>'E-mail ou senha incorretos.'], 401);

$_SESSION['usuario'] = [
    'id'    => $u['id'],
    'nome'  => $u['nome'],
    'email' => $u['email'],
    'tipo'  => $u['tipo'],
];

json_out(['status'=>'ok','usuario'=>$_SESSION['usuario']]);
