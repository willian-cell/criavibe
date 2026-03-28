<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();

$sql = "
    SELECT g.*,
           COUNT(i.id) as total_fotos,
           SUM(CASE WHEN i.selecionada = 1 THEN 1 ELSE 0 END) as total_selecionadas,
           (SELECT i2.caminho_arquivo FROM imagens i2
            WHERE i2.galeria_id = g.id ORDER BY i2.ordem LIMIT 1) as thumb
    FROM galerias g
    LEFT JOIN imagens i ON i.galeria_id = g.id
    WHERE g.usuario_email = ?
    GROUP BY g.id
    ORDER BY g.criado_em DESC
";
$stmt = db()->prepare($sql);
$stmt->execute([$u['email']]);
json_out(['status'=>'ok','galerias'=>$stmt->fetchAll()]);
