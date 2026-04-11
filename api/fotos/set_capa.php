<?php
require_once __DIR__.'/../config.php';

$body = body();
$foto_id = (int)($body['id'] ?? 0);
$galeria_id = (int)($body['galeria_id'] ?? 0);
$token = $body['token'] ?? '';
$remover = (bool)($body['remover'] ?? false);

// Verifica acesso: via sessão OU via token
$acesso_ok = false;
if (!empty($_SESSION['galeria_access'][$galeria_id])) {
    $acesso_ok = true;
} elseif ($token && $galeria_id) {
    $st = db()->prepare("SELECT id FROM galerias WHERE id = ? AND link_token = ? LIMIT 1");
    $st->execute([$galeria_id, $token]);
    if ($st->fetch()) {
        $acesso_ok = true;
        $_SESSION['galeria_access'][$galeria_id] = true;
    }
} else {
    // Se for fotógrafo/admin também pode alterar a capa no seu painel
    $u = me();
    if ($u && ($u['tipo'] === 'fotografo' || $u['tipo'] === 'admin')) {
        $chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
        $chk->execute([$galeria_id, $u['email']]);
        if ($chk->fetch()) $acesso_ok = true;
    }
}

if (!$acesso_ok || !$galeria_id || !$foto_id) {
    json_out(['status'=>'erro','mensagem'=>'Sem permissão ou dados inválidos.'], 403);
}

// Tenta adicionar a coluna is_capa caso ainda não exista (Lazy migration)
try {
    db()->exec("ALTER TABLE imagens ADD COLUMN is_capa TINYINT(1) DEFAULT 0");
} catch (Exception $e) {
    // Se der erro, provavelmente a coluna já existe
}

// Reset as fotos da galeria (apenas uma capa por vez)
$stmt = db()->prepare("UPDATE imagens SET is_capa = 0 WHERE galeria_id = ?");
$stmt->execute([$galeria_id]);

if (!$remover) {
    // Define a nova foto de capa
    $stmt = db()->prepare("UPDATE imagens SET is_capa = 1 WHERE id = ? AND galeria_id = ?");
    $stmt->execute([$foto_id, $galeria_id]);
    json_out(['status'=>'ok', 'mensagem'=>'Capa atualizada com sucesso.']);
} else {
    json_out(['status'=>'ok', 'mensagem'=>'Capa removida com sucesso.']);
}
