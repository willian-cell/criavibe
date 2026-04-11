<?php
require_once __DIR__.'/../config.php';
$u    = require_fotografo();
$body = body();

$id = (int)($body['id'] ?? 0);
if (!$id) json_out(['status'=>'erro','mensagem'=>'ID inválido.'], 400);

// Segurança
$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

// Capturar tema garantindo segurança (evita SQL Injection / XSS nas keys)
$tema = $body['tema'] ?? 'escuro';
if (!in_array($tema, ['escuro', 'claro'])) $tema = 'escuro';

$stmt = db()->prepare("UPDATE galerias SET tema=? WHERE id=?");
$stmt->execute([$tema, $id]);

json_out(['status'=>'ok','mensagem'=>'Tema atualizado.']);
