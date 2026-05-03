<?php
require_once __DIR__.'/../config.php';

// Ativação de Logs Forçada (Ignorando o config.php do servidor)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');
error_reporting(E_ALL);

$u = require_fotografo();

$galeria_id = (int)($_POST['galeria_id'] ?? 0);
error_log("Iniciando Upload: Galeria $galeria_id, Files: " . count($_FILES['fotos']['name'] ?? []));

if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

// Verificar dono
$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$galeria_id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

$files = $_FILES['fotos'] ?? null;
if (!$files) json_out(['status'=>'erro','mensagem'=>'Nenhum arquivo enviado.'], 400);

require_once __DIR__.'/../lib/R2Storage.php';

// Garantir que as constantes existam (Caso o config.php do servidor seja antigo)
if (!defined('R2_ACCESS_KEY')) define('R2_ACCESS_KEY', getenv('R2_ACCESS_KEY_ID'));
if (!defined('R2_SECRET_KEY')) define('R2_SECRET_KEY', getenv('R2_SECRET_KEY'));
if (!defined('R2_BUCKET'))     define('R2_BUCKET',     getenv('R2_BUCKET_NAME'));
if (!defined('R2_ENDPOINT')) {
    define('R2_ENDPOINT', "https://" . getenv('R2_ACCOUNT_ID') . ".r2.cloudflarestorage.com/" . R2_BUCKET);
}

// Instanciar R2
$r2 = new R2Storage(R2_ACCESS_KEY, R2_SECRET_KEY, R2_BUCKET, R2_ENDPOINT);

$allowed = ['image/jpeg','image/png','image/webp','image/gif'];
$enviadas = 0;
$erros = [];

// Suporte a multiple files
$total = is_array($files['name']) ? count($files['name']) : 1;
for ($i = 0; $i < $total; $i++) {
    $name  = is_array($files['name'])  ? $files['name'][$i]  : $files['name'];
    $tmp   = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $type  = is_array($files['type'])  ? $files['type'][$i]  : $files['type'];
    $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
    $size  = is_array($files['size'])  ? $files['size'][$i]  : $files['size'];

    if ($error !== UPLOAD_ERR_OK) { $erros[] = $name; continue; }
    if (!in_array($type, $allowed))  { $erros[] = $name; continue; }

    $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $filename = uniqid('foto_', true).'.'.$ext;
    
    // Caminho no R2: galerias/{id}/{filename}
    $r2Path   = "galerias/{$galeria_id}/{$filename}";
    
    // Upload para o R2
    if (!$r2->upload($tmp, $r2Path, $type)) {
        $erros[] = "$name (Falha no R2)";
        continue;
    }

    // URL Pública do R2
    $caminho = R2_PUBLIC_URL . '/' . $r2Path;

    // ordenação: próximo número
    $ord = db()->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM imagens WHERE galeria_id=?");
    $ord->execute([$galeria_id]);
    $ordem = (int)$ord->fetchColumn();

    $stmt = db()->prepare("INSERT INTO imagens (galeria_id,nome_arquivo,caminho_arquivo,tamanho_bytes,ordem) VALUES (?,?,?,?,?)");
    $stmt->execute([$galeria_id, $name, $caminho, $size, $ordem]);
    $enviadas++;
}

json_out(['status'=>'ok','enviadas'=>$enviadas,'erros'=>$erros]);
