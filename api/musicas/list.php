<?php
require_once __DIR__.'/../config.php';
// Músicas são carregadas pelo cliente — verifica acesso via sessão ou galeria pública

$galeria_id = (int)($_GET['galeria_id'] ?? 0);
if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

// Permite acesso se: fotógrafo logado, OU sessão de cliente válida, OU galeria pública
$acesso = false;
$u = me();
if ($u) {
    $acesso = true; // fotógrafo logado
} elseif (!empty($_SESSION['galeria_access'][$galeria_id])) {
    $acesso = true; // cliente autenticado
} else {
    // Verifica se galeria é pública
    $chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND privacidade='publica' LIMIT 1");
    $chk->execute([$galeria_id]);
    if ($chk->fetch()) $acesso = true;
}

if (!$acesso) json_out(['status'=>'erro','mensagem'=>'Sem acesso.'], 403);

$stmt = db()->prepare("SELECT * FROM musicas WHERE galeria_id=? ORDER BY ordem ASC");
$stmt->execute([$galeria_id]);
json_out(['status'=>'ok','musicas'=>$stmt->fetchAll()]);
