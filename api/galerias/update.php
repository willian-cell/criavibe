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
$max_downloads = max(0, (int)($body['max_downloads'] ?? 0));
$max_selecao  = max(0, (int)($body['max_selecao'] ?? 0));

if (!$nome) json_out(['status'=>'erro','mensagem'=>'Nome obrigatório.'], 400);

if ($senha_raw) {
    $stmt = db()->prepare("UPDATE galerias SET nome=?,descricao=?,privacidade=?,senha=?,max_downloads=?,max_selecao=? WHERE id=?");
    $stmt->execute([$nome, $descricao, $privacidade, password_hash($senha_raw, PASSWORD_DEFAULT), $max_downloads, $max_selecao, $id]);
} else {
    $stmt = db()->prepare("UPDATE galerias SET nome=?,descricao=?,privacidade=?,max_downloads=?,max_selecao=? WHERE id=?");
    $stmt->execute([$nome, $descricao, $privacidade, $max_downloads, $max_selecao, $id]);
}

json_out(['status'=>'ok','mensagem'=>'Galeria atualizada.']);
