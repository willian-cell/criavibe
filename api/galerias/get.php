<?php
require_once __DIR__.'/../config.php';

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if ($id) {
    $stmt = db()->prepare("SELECT * FROM galerias WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
} elseif ($token) {
    $stmt = db()->prepare("SELECT * FROM galerias WHERE link_token = ? LIMIT 1");
    $stmt->execute([$token]);
} else {
    json_out(['status'=>'erro','mensagem'=>'Parâmetro id ou token obrigatório.'], 400);
}

$g = $stmt->fetch();
if (!$g) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

// Se privada, verificar sessão ou token
if ($g['privacidade'] === 'privada') {
    $u = me();
    if (!$u || $u['email'] !== $g['usuario_email'])
        json_out(['status'=>'erro','mensagem'=>'Acesso negado.'], 403);
}

json_out(['status'=>'ok','galeria'=>$g]);
