<?php
require_once __DIR__.'/../config.php';
$u = me();
if (!$u) json_out(['status'=>'erro','mensagem'=>'Não autenticado.'], 401);
json_out(['status'=>'ok','usuario'=>$u]);
