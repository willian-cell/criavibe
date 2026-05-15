<?php
error_log("Config.php carregado por: " . ($_SERVER['REQUEST_URI'] ?? 'cli'));

require_once __DIR__ . '/lib/DotEnv.php';
DotEnv::load(__DIR__ . '/../.env');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', getenv('RAILWAY_ENVIRONMENT') ? 'php://stderr' : __DIR__ . '/error.log');
error_reporting(E_ALL);

function env_val(string $key, ?string $fallback = null): ?string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $fallback : $value;
}

function railway_mysql_url(): array {
    $url = env_val('MYSQL_URL') ?: env_val('DATABASE_URL');
    if (!$url) return [];

    $parts = parse_url($url);
    if (!$parts || !isset($parts['host'])) return [];

    return [
        'host' => $parts['host'],
        'port' => isset($parts['port']) ? (string)$parts['port'] : '3306',
        'user' => isset($parts['user']) ? urldecode($parts['user']) : null,
        'pass' => isset($parts['pass']) ? urldecode($parts['pass']) : null,
        'name' => isset($parts['path']) ? ltrim($parts['path'], '/') : null,
    ];
}

$railwayDb = railway_mysql_url();

define('DB_HOST', $railwayDb['host'] ?? env_val('MYSQLHOST', env_val('DB_HOST', 'localhost')));
define('DB_PORT', $railwayDb['port'] ?? env_val('MYSQLPORT', env_val('DB_PORT', '3306')));
define('DB_NAME', $railwayDb['name'] ?? env_val('MYSQLDATABASE', env_val('DB_NAME', 'criavibe')));
define('DB_USER', $railwayDb['user'] ?? env_val('MYSQLUSER', env_val('DB_USER', 'root')));
define('DB_PASS', $railwayDb['pass'] ?? env_val('MYSQLPASSWORD', env_val('DB_PASSWORD', '')));

define('R2_ACCESS_KEY', env_val('R2_ACCESS_KEY_ID'));
define('R2_SECRET_KEY', env_val('R2_SECRET_KEY'));
define('R2_BUCKET', env_val('R2_BUCKET_NAME'));
define('R2_PUBLIC_URL', rtrim(env_val('R2_PUBLIC_URL', ''), '/'));
define('R2_ENDPOINT', env_val('R2_ACCOUNT_ID') && R2_BUCKET ? "https://" . env_val('R2_ACCOUNT_ID') . ".r2.cloudflarestorage.com/" . R2_BUCKET : '');

$redisUrl = env_val('REDIS_URL');
if ($redisUrl) {
    $parts = parse_url($redisUrl);
    if (isset($parts['host'])) define('REDIS_HOST', $parts['host']);
    if (isset($parts['port'])) define('REDIS_PORT', (string)$parts['port']);
    if (isset($parts['pass'])) define('REDIS_PASSWORD', $parts['pass']);
    if (isset($parts['path'])) {
        $dbIndex = ltrim($parts['path'], '/');
        if ($dbIndex !== '') define('REDIS_DB', $dbIndex);
    }
}

// Redis configuration for job queue
if (!defined('REDIS_HOST')) define('REDIS_HOST', env_val('REDIS_HOST', '127.0.0.1'));
if (!defined('REDIS_PORT')) define('REDIS_PORT', env_val('REDIS_PORT', '6379'));
if (!defined('REDIS_PASSWORD')) define('REDIS_PASSWORD', env_val('REDIS_PASSWORD', ''));
if (!defined('REDIS_DB')) define('REDIS_DB', env_val('REDIS_DB', '0'));

// Worker defaults
define('WORKER_QUEUE_NAME', env_val('WORKER_QUEUE_NAME', 'image_jobs'));
define('WORKER_POLL_TIMEOUT', (int)env_val('WORKER_POLL_TIMEOUT', '5'));

// Feature flags
define('FORCE_DIRECT_UPLOAD', (env_val('FORCE_DIRECT_UPLOAD', '0') === '1'));

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO(
            "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function json_out(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? $_POST;
}

function me(): ?array {
    return $_SESSION['usuario'] ?? null;
}

function require_auth(): array {
    $u = me();
    if (!$u) json_out(['status'=>'erro','mensagem'=>'Nao autenticado.'], 401);
    return $u;
}

function require_fotografo(): array {
    $u = require_auth();
    if (!in_array($u['tipo'], ['fotografo','admin']))
        json_out(['status'=>'erro','mensagem'=>'Sem permissao.'], 403);
    return $u;
}
