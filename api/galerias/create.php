<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();
$body = body();

$nome       = trim($body['nome'] ?? '');
$descricao  = trim($body['descricao'] ?? '');
$privacidade = in_array($body['privacidade']??'', ['publica','privada']) ? $body['privacidade'] : 'privada';
$senha_raw  = $body['senha'] ?? null;
$senha_hash = $senha_raw ? password_hash($senha_raw, PASSWORD_DEFAULT) : null;

if (!$nome) json_out(['status'=>'erro','mensagem'=>'Nome obrigatório.'], 400);

$token = bin2hex(random_bytes(32));

$stmt = db()->prepare("
    INSERT INTO galerias (usuario_email, nome, descricao, privacidade, senha, link_token)
    VALUES (?,?,?,?,?,?)
");
$stmt->execute([$u['email'], $nome, $descricao, $privacidade, $senha_hash, $token]);
$id = db()->lastInsertId();

json_out(['status'=>'ok','id'=>$id,'link_token'=>$token]);
