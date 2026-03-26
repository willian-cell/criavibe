<?php
require_once __DIR__.'/../config.php';

$sql = "
    SELECT g.id, g.nome, g.descricao, g.usuario_email
    FROM galerias g
    WHERE g.privacidade = 'publica'
    ORDER BY g.criado_em DESC
    LIMIT 20
";
$stmt = db()->query($sql);
$gals = $stmt->fetchAll();

// Para cada galeria, pega as primeiras fotos públicas se houver
foreach ($gals as &$g) {
    $f = db()->prepare("SELECT caminho_arquivo FROM imagens WHERE galeria_id=? AND eh_publica=1 ORDER BY ordem ASC LIMIT 3");
    $f->execute([$g['id']]);
    $fotos = $f->fetchAll();
    $g['fotos_destaque'] = implode('|', array_column($fotos, 'caminho_arquivo'));
}

json_out(['status'=>'ok','galerias'=>$gals]);

