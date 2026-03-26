<?php
require_once __DIR__.'/../config.php';

$id    = (int)($_GET['id'] ?? 0);
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

// Acesso por token: o token em si já é a autorização (link direto)
// Acesso por id: só o dono pode ver galeria privada
if (!$token && $id) {
    if ($g['privacidade'] === 'privada') {
        $u = me();
        if (!$u || $u['email'] !== $g['usuario_email'])
            json_out(['status'=>'erro','mensagem'=>'Acesso negado.'], 403);
    }
}

// Retorna galeria sem expor a senha hash
unset($g['senha']);
json_out(['status'=>'ok','galeria'=>$g]);
