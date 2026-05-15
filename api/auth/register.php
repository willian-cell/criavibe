<?php
require_once __DIR__.'/../config.php';

$body = body();
$nome  = trim($body['nome']  ?? '');
$email = strtolower(trim($body['email'] ?? ''));
$senha = $body['senha'] ?? '';

if (!$nome || !$email || !$senha)
    json_out(['status'=>'erro','mensagem'=>'Todos os campos sao obrigatorios.'], 400);
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    json_out(['status'=>'erro','mensagem'=>'E-mail invalido.'], 400);
if (strlen($senha) < 6)
    json_out(['status'=>'erro','mensagem'=>'Senha minima de 6 caracteres.'], 400);

try {
    $chk = db()->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $chk->execute([$email]);
    if ($chk->fetch()) json_out(['status'=>'erro','mensagem'=>'E-mail ja cadastrado.'], 409);

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $ins = db()->prepare("INSERT INTO usuarios (nome,email,senha,tipo) VALUES (?,?,?,'fotografo')");
    $ins->execute([$nome, $email, $hash]);
} catch (PDOException $e) {
    error_log('Erro no cadastro: ' . $e->getMessage());
    json_out([
        'status'=>'erro',
        'mensagem'=>'Nao foi possivel criar a conta. Rode /api/db_migrations.php no Railway e tente novamente.'
    ], 500);
}

json_out(['status'=>'ok','mensagem'=>'Conta criada com sucesso!']);
