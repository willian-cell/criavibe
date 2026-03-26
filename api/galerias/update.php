<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();
$body = body();

$id = (int)($body['id'] ?? 0);
if (!$id) json_out(['status'=>'erro','mensagem'=>'ID inválido.'], 400);

// Verificar dono
$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

$nome        = trim($body['nome'] ?? '');
$descricao   = trim($body['descricao'] ?? '');
$privacidade = in_array($body['privacidade']??'', ['publica','privada']) ? $body['privacidade'] : 'privada';
$senha_raw   = $body['senha'] ?? null;

if (!$nome) json_out(['status'=>'erro','mensagem'=>'Nome obrigatório.'], 400);

if ($senha_raw) {
    $stmt = db()->prepare("UPDATE galerias SET nome=?,descricao=?,privacidade=?,senha=? WHERE id=?");
    $stmt->execute([$nome, $descricao, $privacidade, password_hash($senha_raw, PASSWORD_DEFAULT), $id]);
} else {
    $stmt = db()->prepare("UPDATE galerias SET nome=?,descricao=?,privacidade=? WHERE id=?");
    $stmt->execute([$nome, $descricao, $privacidade, $id]);
}

json_out(['status'=>'ok','mensagem'=>'Galeria atualizada.']);
