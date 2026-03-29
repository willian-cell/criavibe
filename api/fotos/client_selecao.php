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
    
    // Pegar estado atual para saber se estamos selecionando (aumentando contador)
    $curr = db()->prepare("SELECT selecionada FROM imagens WHERE id=? AND galeria_id=? LIMIT 1");
    $curr->execute([$id, $galeria_id]);
    $is_sel = (bool)$curr->fetchColumn();

    if (!$is_sel) {
        // Tentativa de selecionar: verificar limite
        $gstmt = db()->prepare("SELECT max_selecao FROM galerias WHERE id=? LIMIT 1");
        $gstmt->execute([$galeria_id]);
        $limit = (int)$gstmt->fetchColumn();

        if ($limit > 0) {
            $count_q = db()->prepare("SELECT COUNT(*) FROM imagens WHERE galeria_id=? AND selecionada=1");
            $count_q->execute([$galeria_id]);
            $count = (int)$count_q->fetchColumn();
            if ($count >= $limit) {
                json_out(['status'=>'erro','mensagem'=>'Limite de seleção atingido.'], 400);
            }
        }
    }

    $stmt = db()->prepare("UPDATE imagens SET selecionada = NOT selecionada WHERE id=? AND galeria_id=?");
    $stmt->execute([$id, $galeria_id]);

} elseif ($acao === 'all') {
    $gstmt = db()->prepare("SELECT max_selecao FROM galerias WHERE id=? LIMIT 1");
    $gstmt->execute([$galeria_id]);
    $limit = (int)$gstmt->fetchColumn();

    if ($limit > 0) {
        // Se há limite, primeiro desmarcamos tudo por segurança e marcamos apenas os primeiros N
        db()->prepare("UPDATE imagens SET selecionada = 0 WHERE galeria_id=?")->execute([$galeria_id]);
        $stmt = db()->prepare("UPDATE imagens SET selecionada = 1 WHERE galeria_id=? LIMIT $limit");
        $stmt->execute([$galeria_id]);
    } else {
        $stmt = db()->prepare("UPDATE imagens SET selecionada = 1 WHERE galeria_id=?");
        $stmt->execute([$galeria_id]);
    }

} elseif ($acao === 'clear') {
    $stmt = db()->prepare("UPDATE imagens SET selecionada = 0 WHERE galeria_id=?");
    $stmt->execute([$galeria_id]);
}

json_out(['status'=>'ok']);
