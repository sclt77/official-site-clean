<?php

namespace App\Controllers;

use App\Core\Database;
use App\Models\UserModel;

class InstallController
{
    public function index(): void
    {
        if (file_exists(dirname(__DIR__, 2) . '/storage/installed.lock')) {
            header('Location: /index.php?path=login');
            exit;
        }

        $step = (int) ($_POST['step'] ?? $_GET['step'] ?? 1);
        $error = '';

        if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            try {
                $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
                $dbPort = (int) ($_POST['db_port'] ?? 3306);
                $dbName = trim((string) ($_POST['db_name'] ?? ''));
                $dbUser = trim((string) ($_POST['db_user'] ?? ''));
                $dbPass = (string) ($_POST['db_pass'] ?? '');
                if ($dbName === '' || $dbUser === '') throw new \RuntimeException('请填写数据库配置');
                $this->ensureDatabase($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
                $this->writeDbConfig($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
                Database::connection();
                $step = 3;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                $step = 2;
            }
        }

        if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
            csrf_verify();
            try {
                Database::connection();
                $this->runInstallSql();
                $step = 4;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                $step = 3;
            }
        }

        if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_email'])) {
            csrf_verify();
            try {
                $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
                $adminName = trim((string) ($_POST['admin_name'] ?? 'admin'));
                $adminPass = (string) ($_POST['admin_pass'] ?? '');
                if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 6) throw new \RuntimeException('管理员信息不完整');
                $userModel = new UserModel();
                if (!$userModel->findByEmail($adminEmail)) $userModel->create($adminEmail, $adminPass, $adminName, 'admin', true);
                $this->writeInstalledLock();
                $step = 5;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                $step = 4;
            }
        }

        require dirname(__DIR__) . '/views/install/index.php';
    }

    private function ensureDatabase(string $host, int $port, string $db, string $user, string $pass): void
    {
        $safeDb = preg_replace('/[^a-zA-Z0-9_]/', '', $db);
        if ($safeDb === '') {
            throw new \RuntimeException('数据库名不合法');
        }
        $pdo = new \PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port), $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function runInstallSql(): void
    {
        $sqlFile = dirname(__DIR__, 2) . '/database/install.sql';
        $sql = trim((string) file_get_contents($sqlFile));
        if ($sql === '') {
            return;
        }

        $db = Database::connection();
        $parts = preg_split('/;\s*(\r\n|\r|\n)/', $sql) ?: [];
        foreach ($parts as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }
            $db->exec($stmt);
        }
    }

    private function writeDbConfig(string $host, int $port, string $db, string $user, string $pass): void
    {
        $cfg = [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $db,
            'username' => $user,
            'password' => $pass,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];
        $content = "<?php\nreturn " . var_export($cfg, true) . ";\n";
        $this->safeWriteFile(dirname(__DIR__, 2) . '/config/database.php', $content, '数据库配置文件');
    }

    private function writeInstalledLock(): void
    {
        $this->safeWriteFile(dirname(__DIR__, 2) . '/storage/installed.lock', date('Y-m-d H:i:s'), '安装锁文件');
    }

    private function safeWriteFile(string $path, string $content, string $label): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException($label . '目录创建失败：' . $dir);
        }
        if (!is_writable($dir)) {
            throw new \RuntimeException($label . '目录不可写：' . $dir . '，请检查目录权限或所属用户。');
        }
        $bytes = @file_put_contents($path, $content, LOCK_EX);
        if ($bytes === false) {
            throw new \RuntimeException($label . '写入失败：' . $path . '，请检查文件权限或磁盘空间。');
        }
    }
}
