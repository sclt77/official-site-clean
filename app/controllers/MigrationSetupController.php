<?php

namespace App\Controllers;

class MigrationSetupController
{
    private string $root;
    private string $pending;
    private string $completed;
    private string $manifest;

    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);
        $this->pending = $this->root . '/migration/pending.lock';
        $this->completed = $this->root . '/migration/completed.lock';
        $this->manifest = $this->root . '/migration/manifest.json';
    }

    public function index(): void
    {
        if (!$this->available()) {
            http_response_code(404);
            exit('迁移配置入口不可用');
        }
        $error = '';
        $success = '';
        $checks = $this->checks();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            try {
                $this->handleSubmit();
                $success = '迁移配置已完成，入口已锁定。';
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        require dirname(__DIR__) . '/views/migration/setup.php';
    }

    private function available(): bool
    {
        return is_file($this->pending) && !is_file($this->completed);
    }

    private function handleSubmit(): void
    {
        $token = trim((string)($_POST['token'] ?? ''));
        $expected = trim((string)@file_get_contents($this->pending));
        if ($token === '' || !hash_equals($expected, $token)) throw new \RuntimeException('迁移令牌错误');
        $host = trim((string)($_POST['host'] ?? 'localhost'));
        $port = (int)($_POST['port'] ?? 3306);
        $database = trim((string)($_POST['database'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $siteUrl = rtrim(trim((string)($_POST['site_url'] ?? '')), '/');
        if ($host === '' || $port <= 0 || $database === '' || $username === '') throw new \RuntimeException('请填写完整数据库信息');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);
        try {
            $pdo = new \PDO($dsn, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('数据库连接失败：' . $e->getMessage());
        }
        $sqlFile = $this->root . '/migration/database.sql';
        if (is_file($sqlFile) && !empty($_POST['import_database'])) {
            $this->importSql($pdo, $sqlFile);
        }
        $config = [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];
        $this->writePhpArray($this->root . '/config/database.php', $config);
        if ($siteUrl !== '') {
            $appFile = $this->root . '/config/app.php';
            $app = is_file($appFile) ? require $appFile : [];
            if (is_array($app)) {
                $app['url'] = $siteUrl;
                $this->writePhpArray($appFile, $app);
            }
        }
        @mkdir(dirname($this->completed), 0755, true);
        file_put_contents($this->completed, 'completed_at=' . date('c') . "\n");
        @unlink($this->pending);
    }

    private function importSql(\PDO $pdo, string $file): void
    {
        $fh = fopen($file, 'r');
        if (!$fh) throw new \RuntimeException('无法读取数据库备份');
        $statement = '';
        $delimiter = ';';
        $inString = null;
        while (($line = fgets($fh)) !== false) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#')) continue;
            if (preg_match('/^DELIMITER\s+(.+)$/i', $trim, $m)) {
                $delimiter = $m[1];
                continue;
            }
            if (str_starts_with($trim, '/*') && str_ends_with($trim, '*/')) {
                if (preg_match('#^/\*!\d+\s+(.*?)\s*\*/;?$#', $trim, $m)) {
                    $sql = rtrim($m[1], ';');
                    if ($sql !== '') $pdo->exec($sql);
                }
                continue;
            }
            $statement .= $line;
            $len = strlen($statement);
            for ($i = 0; $i < $len; $i++) {
                $ch = $statement[$i];
                $prev = $i > 0 ? $statement[$i - 1] : '';
                if (($ch === "'" || $ch === '"') && $prev !== '\\') {
                    $inString = $inString === $ch ? null : ($inString ?? $ch);
                }
            }
            if ($inString === null && str_ends_with(rtrim($statement), $delimiter)) {
                $sql = trim(substr(rtrim($statement), 0, -strlen($delimiter)));
                $statement = '';
                if ($sql !== '') $pdo->exec($sql);
            }
        }
        $tail = trim($statement);
        if ($tail !== '') $pdo->exec($tail);
        fclose($fh);
    }

    private function checks(): array
    {
        return [
            ['label'=>'迁移标记', 'ok'=>is_file($this->pending), 'detail'=>'migration/pending.lock'],
            ['label'=>'数据库备份', 'ok'=>is_file($this->root . '/migration/database.sql'), 'detail'=>'migration/database.sql'],
            ['label'=>'配置目录可写', 'ok'=>is_writable($this->root . '/config'), 'detail'=>'config/'],
            ['label'=>'迁移目录可写', 'ok'=>is_writable($this->root . '/migration'), 'detail'=>'migration/'],
        ];
    }

    private function writePhpArray(string $file, array $data): void
    {
        $content = "<?php\nreturn " . var_export($data, true) . ";\n";
        if (file_put_contents($file, $content, LOCK_EX) === false) throw new \RuntimeException('写入配置失败：' . basename($file));
    }
}
