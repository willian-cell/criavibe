<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();
$body = body();

$nome     = trim($body['nome'] ?? '');
$email    = strtolower(trim($body['email'] ?? ''));
$telefone = trim($body['telefone'] ?? '');

if (!$nome) json_out(['status'=>'erro','mensagem'=>'Nome obrigatório.'], 400);

// Gera senha curta aleatória
$senha = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

$stmt = db()->prepare("INSERT INTO clientes (fotografo_email,nome,email,telefone,senha_acesso) VALUES (?,?,?,?,?)");
$stmt->execute([$u['email'], $nome, $email ?: null, $telefone ?: null, $senha]);
$id = db()->lastInsertId();

json_out(['status'=>'ok','id'=>$id,'senha_acesso'=>$senha]);
