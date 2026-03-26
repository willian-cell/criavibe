<?php
require_once __DIR__.'/../config.php';

$sql = "
    SELECT g.id, g.nome, g.descricao, g.usuario_email,
           GROUP_CONCAT(i.caminho_arquivo ORDER BY i.eh_publica DESC, i.ordem LIMIT 3 SEPARATOR '|') as fotos_destaque
    FROM galerias g
    LEFT JOIN imagens i ON i.galeria_id = g.id AND i.eh_publica = 1
    WHERE g.privacidade = 'publica'
    GROUP BY g.id
    ORDER BY g.criado_em DESC
    LIMIT 20
";
$stmt = db()->query($sql);
json_out(['status'=>'ok','galerias'=>$stmt->fetchAll()]);
