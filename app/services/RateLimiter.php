<?php

namespace App\Services;

/**
 * 简单的基于文件的 API 频率限制
 */
class RateLimiter
{
    private string $storageDir;
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 30, int $windowSeconds = 60)
    {
        $this->storageDir = dirname(__DIR__, 2) . '/storage/ratelimit';
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }

    public function check(string $key): bool
    {
        $file = $this->storageDir . '/' . md5($key) . '.json';
        $now = time();
        $data = [];

        if (file_exists($file)) {
            $raw = file_get_contents($file);
            $data = json_decode($raw, true) ?: [];
        }

        // 清理过期记录
        $data = array_filter($data, fn($t) => $now - $t < $this->windowSeconds);

        if (count($data) >= $this->maxRequests) {
            return false; // 超限
        }

        $data[] = $now;
        file_put_contents($file, json_encode(array_values($data)), LOCK_EX);
        return true;
    }

    public function ip(): string
    {
        $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $trustProxy = (string)($_SERVER['TRUST_PROXY_HEADERS'] ?? getenv('TRUST_PROXY_HEADERS') ?: '') === '1';
        if ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $candidate = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : 'unknown';
    }
}
