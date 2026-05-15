<?php
/**
 * Queue - wrapper simples para Redis lists (RPUSH / BLPOP)
 * Requer extensão phpredis instalada (class Redis).
 */
class Queue {
    private $redis;

    public function __construct() {
        if (!class_exists('Redis')) throw new Exception('Extensão phpredis não disponível. Instale ou use outra implementação.');
        $r = new Redis();
        $connected = $r->connect(REDIS_HOST, (int)REDIS_PORT);
        if (!$connected) throw new Exception('Não foi possível conectar ao Redis em '.REDIS_HOST.':'.REDIS_PORT);
        if (REDIS_PASSWORD !== '') $r->auth(REDIS_PASSWORD);
        if (REDIS_DB !== '') $r->select((int)REDIS_DB);
        $this->redis = $r;
    }

    public function push(string $queue, array $payload): bool {
        $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
        return $this->redis->rPush($queue, $data) > 0;
    }

    /**
     * Bloqueante com timeout em segundos. Retorna null se timeout.
     */
    public function pop(string $queue, int $timeout = 5): ?array {
        $res = $this->redis->blPop([$queue], $timeout);
        if (!$res || !is_array($res) || count($res) < 2) return null;
        $json = $res[1];
        $payload = json_decode($json, true);
        return is_array($payload) ? $payload : null;
    }
}
