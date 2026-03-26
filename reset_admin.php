<?php
// ARQUIVO TEMPORÁRIO — auto-deleta após executar
require_once __DIR__.'/api/config.php';

$nova_senha = 'Deusjeova159$';
$hash = password_hash($nova_senha, PASSWORD_DEFAULT);

$stmt = db()->prepare("UPDATE usuarios SET senha=? WHERE email='admin@criavibe.com'");
$stmt->execute([$hash]);

echo "<h2 style='color:green'>Senha resetada com sucesso!</h2>";
echo "<p>Login: <strong>admin@criavibe.com</strong></p>";
echo "<p>Senha: <strong>Deusjeova159\$</strong></p>";
echo "<br><a href='/entrar.html'>→ Ir para o Login</a>";

// Auto-deleta este arquivo após executar
@unlink(__FILE__);
