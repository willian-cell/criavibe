<?php
require_once __DIR__.'/../config.php';

$body = body();
$acao = $body['acao'] ?? 'toggle'; // 'toggle', 'all', 'clear'
$galeria_id = (int)($body['galeria_id'] ?? 0);
$token = $body['token'] ?? '';

// Verifica acesso: via sessão OU via token (fallback para quando a sessão PHP expira no Hostinger)
$acesso_ok = false;

if (!empty($_SESSION['galeria_access'][$galeria_id])) {
    $acesso_ok = true;
} elseif ($token && $galeria_id) {
    // Fallback: valida token direto no banco
    $st = db()->prepare("SELECT id FROM galerias WHERE id = ? AND link_token = ? LIMIT 1");
    $st->execute([$galeria_id, $token]);
    if ($st->fetch()) {
        $acesso_ok = true;
        // Restaura a sessão para as próximas requisições
        $_SESSION['galeria_access'][$galeria_id] = true;
    }
}

if (!$acesso_ok || !$galeria_id) {
    json_out(['status'=>'erro','mensagem'=>'Sessão expirada ou sem acesso.'], 403);
}

if ($acao === 'toggle') {
    $id = (int)($body['id'] ?? 0);
    $stmt = db()->prepare("UPDATE imagens SET selecionada = NOT selecionada WHERE id=? AND galeria_id=?");
    $stmt->execute([$id, $galeria_id]);

} elseif ($acao === 'all') {
    $stmt = db()->prepare("UPDATE imagens SET selecionada = 1 WHERE galeria_id=?");
    $stmt->execute([$galeria_id]);

} elseif ($acao === 'clear') {
    $stmt = db()->prepare("UPDATE imagens SET selecionada = 0 WHERE galeria_id=?");
    $stmt->execute([$galeria_id]);
}

json_out(['status'=>'ok']);
