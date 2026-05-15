<?php
/**
 * RateLimiter - simples limiter por usuário usando Redis
 */
class RateLimiter {
    private $redis;

    public function __construct() {
        if (!class_exists('Redis')) throw new Exception('phpredis required');
        $r = new Redis();
        $r->connect(REDIS_HOST, (int)REDIS_PORT);
        if (REDIS_PASSWORD !== '') $r->auth(REDIS_PASSWORD);
        if (REDIS_DB !== '') $r->select((int)REDIS_DB);
        $this->redis = $r;
    }

    // Limita X ações por janela (seconds). Retorna true se permitido.
    public function allow(string $key, int $maxActions, int $windowSec): bool {
        $now = time();
        $redisKey = "rl:{$key}:" . floor($now / $windowSec);
        $count = $this->redis->incr($redisKey);
        if ($count === 1) $this->redis->expire($redisKey, $windowSec + 1);
        return ($count <= $maxActions);
    }
}
