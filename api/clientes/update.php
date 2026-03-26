<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();
$body = body();

$id    = (int)($body['id'] ?? 0);
$nome  = trim($body['nome'] ?? '');
$email = strtolower(trim($body['email'] ?? ''));
$tel   = trim($body['telefone'] ?? '');
$senha = trim($body['senha_acesso'] ?? '');

if (!$id || !$nome || !$senha) json_out(['status'=>'erro','mensagem'=>'ID, Nome e Senha são obrigatórios.'], 400);

// Verifica dono
$chk = db()->prepare("SELECT id FROM clientes WHERE id=? AND fotografo_email=?");
$chk->execute([$id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Cliente não encontrado ou acesso restrito'], 404);

$stmt = db()->prepare("UPDATE clientes SET nome=?, email=?, telefone=?, senha_acesso=? WHERE id=?");
$stmt->execute([$nome, $email ?: null, $tel ?: null, $senha, $id]);

json_out(['status'=>'ok']);
