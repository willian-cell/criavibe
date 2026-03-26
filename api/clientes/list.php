<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();
$stmt = db()->prepare("SELECT * FROM clientes WHERE fotografo_email=? ORDER BY nome ASC");
$stmt->execute([$u['email']]);
json_out(['status'=>'ok','clientes'=>$stmt->fetchAll()]);
