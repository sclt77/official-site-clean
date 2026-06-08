<?php

namespace App\Services;

use ZipArchive;

class SiteMigrationService
{
    private string $root;
    private string $backupDir;

    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);
        $this->backupDir = $this->root . '/storage/migration-backups';
    }

    public function listPackages(): array
    {
        if (!is_dir($this->backupDir)) return [];
        $rows = [];
        foreach (glob($this->backupDir . '/*.zip') ?: [] as $file) {
            $rows[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file) ?: 0,
                'created_at' => date('Y-m-d H:i:s', filemtime($file) ?: time()),
            ];
        }
        usort($rows, static fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $rows;
    }

    public function packagePath(string $name): string
    {
        $name = basename($name);
        if (!preg_match('/^official-site-migration-[0-9]{14}-[a-f0-9]{8}\.zip$/', $name)) {
            throw new \RuntimeException('备份文件名无效');
        }
        $path = $this->backupDir . '/' . $name;
        if (!is_file($path)) throw new \RuntimeException('备份不存在');
        return $path;
    }

    public function deletePackage(string $name): void
    {
        $path = $this->packagePath($name);
        $this->log('delete package name=' . basename($path));
        @unlink($path);
        $this->writeStatus(['state' => 'deleted', 'step' => 'deleted', 'name' => basename($path), 'message' => '迁移包已删除']);
    }

    public function preflight(?string $mysqldumpPath = null): array
    {
        $checks = [];
        $resolvedDump = $this->resolveMysqldump($mysqldumpPath ?: null);
        $checks[] = ['label'=>'ZipArchive 扩展', 'ok'=>extension_loaded('zip'), 'detail'=>extension_loaded('zip') ? '已启用' : '未启用'];
        $checks[] = ['label'=>'mysqldump 命令', 'ok'=>true, 'detail'=>$resolvedDump !== '' ? $resolvedDump . '（使用 --no-tablespaces）' : '不可用，将自动使用 PHP/PDO 兜底导出'];
        $checks[] = ['label'=>'备份目录可写', 'ok'=>@is_dir($this->backupDir) ? @is_writable($this->backupDir) : @is_writable(dirname($this->backupDir)), 'detail'=>$this->backupDir];
        $checks[] = ['label'=>'数据库配置', 'ok'=>@is_file($this->root . '/config/database.php'), 'detail'=>'config/database.php'];
        $checks[] = ['label'=>'storage 目录', 'ok'=>@is_dir($this->root . '/storage'), 'detail'=>'storage'];
        return $checks;
    }

    public function status(): array
    {
        $file = $this->backupDir . '/status.json';
        if (!is_file($file)) return [];
        $data = json_decode((string)@file_get_contents($file), true);
        if (!is_array($data)) return [];
        if (($data['state'] ?? '') === 'success' && !empty($data['name'])) {
            $path = $this->backupDir . '/' . basename((string)$data['name']);
            if (!is_file($path)) {
                $data['state'] = 'failed';
                $data['step'] = 'missing_file';
                $data['message'] = '迁移包状态曾显示成功，但文件已不存在；可能被删除、目录被清理或生成后移动失败。请重新生成。';
                $data['missing_path'] = $path;
                $this->writeStatus($data);
            }
        }
        return $data;
    }

    private function writeStatus(array $data): void
    {
        $this->ensureBackupDir();
        $file = $this->backupDir . '/status.json';
        $data['updated_at'] = date('Y-m-d H:i:s');
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        @chmod($file, 0660);
    }

    private function ensureBackupDir(): void
    {
        if (@is_dir($this->backupDir)) return;
        if (!@mkdir($this->backupDir, 0750, true) && !@is_dir($this->backupDir)) {
            throw new \RuntimeException('无法创建迁移备份目录：' . $this->backupDir);
        }
        @chmod($this->backupDir, 0750);
    }

    public function packageManifest(string $name): array
    {
        $path = $this->packagePath($name);
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return [];
        $raw = (string)$zip->getFromName('migration/manifest.json');
        $zip->close();
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function commandExists(string $cmd): bool
    {
        return $this->commandPath($cmd) !== '';
    }

    private function commandPath(string $cmd): string
    {
        if (!function_exists('shell_exec')) return '';
        return trim((string)\shell_exec('command -v ' . \escapeshellarg($cmd) . ' 2>/dev/null'));
    }


    private function pathAllowedByOpenBasedir(string $path): bool
    {
        $basedir = (string) ini_get('open_basedir');
        if ($basedir === '') return true;
        $normalized = str_replace('\\', '/', $path);
        foreach (explode(PATH_SEPARATOR, $basedir) as $base) {
            $base = trim($base);
            if ($base === '') continue;
            $baseReal = @realpath($base) ?: $base;
            $baseNorm = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
            if ($baseNorm === './') $baseNorm = rtrim(str_replace('\\', '/', getcwd() ?: '.'), '/') . '/';
            if (str_starts_with($normalized, $baseNorm) || rtrim($normalized, '/') === rtrim($baseNorm, '/')) {
                return true;
            }
        }
        return false;
    }

    private function executableFileExists(string $path): bool
    {
        if ($path === '' || !$this->pathAllowedByOpenBasedir($path)) return false;
        return @is_file($path) && @is_executable($path);
    }

    public function resolveMysqldump(?string $preferred = null): string
    {
        $candidates = [];
        if ($preferred !== null && trim($preferred) !== '') $candidates[] = trim($preferred);
        $auto = $this->commandPath('mysqldump');
        if ($auto !== '') $candidates[] = $auto;
        array_push($candidates,
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/www/server/mysql/bin/mysqldump',
            '/www/server/mariadb/bin/mysqldump',
            '/www/server/panel/pyenv/bin/mysqldump'
        );
        foreach (array_unique($candidates) as $path) {
            if ($path !== '' && $this->executableFileExists($path)) return $path;
        }
        return '';
    }

    public function createPackage(?string $mysqldumpPath = null): array
    {
        if (!extension_loaded('zip')) throw new \RuntimeException('服务器未启用 ZipArchive');
        $this->ensureBackupDir();
        $token = bin2hex(random_bytes(16));
        $stamp = date('YmdHis');
        $filename = 'official-site-migration-' . $stamp . '-' . substr($token, 0, 8) . '.zip';
        $path = $this->backupDir . '/' . $filename;
        $partPath = $path . '.part';
        $tmpSql = $this->backupDir . '/database-' . $stamp . '.sql';
        $finished = false;
        $this->writeStatus(['state' => 'running', 'step' => 'start', 'name' => $filename, 'message' => '开始生成迁移包']);
        register_shutdown_function(function () use (&$finished, $filename, $path, $partPath, $tmpSql) {
            if ($finished) return;
            if (@is_file($path)) return;
            $err = error_get_last();
            if ($err && !in_array((int)($err['type'] ?? 0), [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $err = null;
            }
            $current = $this->status();
            if (in_array((string)($current['state'] ?? ''), ['success', 'failed'], true) && (($current['name'] ?? '') === $filename)) return;
            $msg = $err ? (($err['message'] ?? 'unknown') . ' @ ' . ($err['file'] ?? '') . ':' . ($err['line'] ?? '')) : '请求结束但未生成文件，请查看上一步状态或日志';
            $this->log('create interrupted ' . $filename . ' reason=' . $msg);
            $this->writeStatus(['state' => 'failed', 'step' => 'interrupted', 'name' => $filename, 'message' => '生成未完成：' . $msg]);
            if (!@is_file($path)) @unlink($partPath);
            @unlink($tmpSql);
        });
        @unlink($partPath);
        $this->log('start create ' . $filename . ' path=' . $path);

        try {
        $this->writeStatus(['state' => 'running', 'step' => 'database', 'name' => $filename, 'message' => '正在导出数据库']);
        $this->dumpDatabase($tmpSql, $mysqldumpPath);
        $this->log('database dumped bytes=' . (@is_file($tmpSql) ? filesize($tmpSql) : 0));

        $zip = new ZipArchive();
        $this->writeStatus(['state' => 'running', 'step' => 'zip', 'name' => $filename, 'message' => '正在打包源码与 storage']);
        if ($zip->open($partPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpSql);
            throw new \RuntimeException('创建 ZIP 失败');
        }

        $manifest = [
            'type' => 'official_site_migration',
            'site' => 'www.claybbs.com',
            'created_at' => date('c'),
            'token' => $token,
            'root' => basename($this->root),
            'php_version' => PHP_VERSION,
            'estimated_size' => $this->directorySize($this->root),
            'notes' => '解压到新服务器站点目录后，首次访问 /index.php?path=migration-setup 并输入 token 完成一次性配置。',
        ];
        $zip->addFromString('migration/manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $zip->addFromString('migration/pending.lock', $token . "\n");
        $zip->addFromString('migration/restore.md', $this->restoreReadme($token));
        $zip->addFile($tmpSql, 'migration/database.sql');

        $this->addDirectory($zip, $this->root, '', [
            '#^storage/migration-backups/#',
            '#^storage/logs/#',
            '#^storage/cache/#',
            '#^\.git/#',
        ]);
        $zip->close();
        $this->log('zip closed exists=' . (@is_file($partPath) ? 'yes' : 'no') . ' bytes=' . (@is_file($partPath) ? filesize($partPath) : 0));
        @unlink($tmpSql);
        $verify = new ZipArchive();
        $this->writeStatus(['state' => 'running', 'step' => 'verify', 'name' => $filename, 'message' => '正在校验迁移包']);
        if ($verify->open($partPath, ZipArchive::CHECKCONS) !== true) {
            @unlink($partPath);
            throw new \RuntimeException('ZIP 完整性校验失败');
        }
        if ($verify->locateName('migration/manifest.json') === false || $verify->locateName('migration/database.sql') === false) {
            $verify->close();
            @unlink($partPath);
            throw new \RuntimeException('迁移包关键文件缺失');
        }
        $verify->close();
        if (!@rename($partPath, $path)) {
            @unlink($partPath);
            throw new \RuntimeException('迁移包写入完成但改名失败，请检查目录权限');
        }
        @chmod($path, 0640);
        $finished = true;
        $this->writeStatus(['state' => 'success', 'step' => 'done', 'name' => $filename, 'message' => '迁移包已生成', 'size' => filesize($path) ?: 0, 'path' => $path]);
        $this->log('zip verified final bytes=' . (@is_file($path) ? filesize($path) : 0));
        return ['name' => $filename, 'path' => $path, 'size' => filesize($path) ?: 0, 'token' => $token];
        } catch (\Throwable $e) {
            $finished = true;
            @unlink($partPath);
            @unlink($tmpSql);
            $message = $e->getMessage() ?: get_class($e);
            $this->log('create failed ' . $filename . ' error=' . $message);
            $this->writeStatus(['state' => 'failed', 'step' => 'exception', 'name' => $filename, 'message' => '生成失败：' . $message]);
            throw $e;
        }
    }

    private function addDirectory(ZipArchive $zip, string $dir, string $prefix, array $excludePatterns): void
    {
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $full = $dir . '/' . $item;
            $rel = ltrim($prefix . '/' . $item, '/');
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $rel) || preg_match($pattern, $rel . '/')) continue 2;
            }
            if (is_dir($full)) {
                $zip->addEmptyDir($rel);
                $this->addDirectory($zip, $full, $rel, $excludePatterns);
            } elseif (is_file($full)) {
                $zip->addFile($full, $rel);
            }
        }
    }

    private function dumpDatabase(string $out, ?string $mysqldumpPath = null): void
    {
        $cfg = require $this->root . '/config/database.php';
        $host = (string)($cfg['host'] ?? 'localhost');
        $port = (int)($cfg['port'] ?? 3306);
        $db = (string)($cfg['database'] ?? '');
        $user = (string)($cfg['username'] ?? '');
        $pass = (string)($cfg['password'] ?? '');
        if ($db === '' || $user === '') throw new \RuntimeException('数据库配置不完整');
        $dump = $this->resolveMysqldump($mysqldumpPath);
        if (!\function_exists('proc_open')) {
            $this->log('proc_open unavailable, fallback to PDO exporter');
            $this->dumpDatabaseWithPdo($out, $cfg);
            return;
        }
        if ($dump === '') {
            $this->log('mysqldump unavailable, fallback to PDO exporter');
            $this->dumpDatabaseWithPdo($out, $cfg);
            return;
        }
        $cmd = [$dump, '--single-transaction', '--quick', '--no-tablespaces', '--default-character-set=utf8mb4', '-h' . $host, '-P' . $port, '-u' . $user, $db];
        $env = $_ENV;
        $env['MYSQL_PWD'] = $pass;
        $descriptors = [1 => ['file', $out, 'w'], 2 => ['pipe', 'w']];
        $proc = @\proc_open($cmd, $descriptors, $pipes, $this->root, $env);
        if (!is_resource($proc)) {
            $this->log('mysqldump start failed, fallback to PDO exporter');
            $this->dumpDatabaseWithPdo($out, $cfg);
            return;
        }
        $err = \stream_get_contents($pipes[2]);
        \fclose($pipes[2]);
        $code = \proc_close($proc);
        if ($code !== 0 || !@is_file($out) || filesize($out) === 0) {
            $this->log('mysqldump failed code=' . $code . ' err=' . trim((string)$err) . ', fallback to PDO exporter');
            @unlink($out);
            $this->dumpDatabaseWithPdo($out, $cfg);
            return;
        }
    }

    private function dumpDatabaseWithPdo(string $out, array $cfg): void
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cfg['host'], (int)$cfg['port'], $cfg['database'], $cfg['charset'] ?? 'utf8mb4');
        $pdo = new \PDO($dsn, (string)$cfg['username'], (string)$cfg['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        $fh = fopen($out, 'w');
        if (!$fh) throw new \RuntimeException('无法创建数据库导出文件');
        fwrite($fh, "-- Clay official site migration database dump\n");
        fwrite($fh, '-- Generated at ' . date('c') . "\n");
        fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(\PDO::FETCH_NUM);
        foreach ($tables as $row) {
            $table = (string)$row[0];
            $quoted = '`' . str_replace('`', '``', $table) . '`';
            fwrite($fh, "DROP TABLE IF EXISTS {$quoted};\n");
            $create = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(\PDO::FETCH_ASSOC);
            $createSql = (string)($create['Create Table'] ?? array_values($create)[1] ?? '');
            if ($createSql === '') throw new \RuntimeException('无法读取表结构：' . $table);
            fwrite($fh, $createSql . ";\n\n");

            $stmt = $pdo->query('SELECT * FROM ' . $quoted, \PDO::FETCH_ASSOC);
            $batch = [];
            $columns = null;
            while ($record = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if ($columns === null) {
                    $columns = array_keys($record);
                }
                $vals = [];
                foreach ($record as $value) {
                    $vals[] = $value === null ? 'NULL' : $pdo->quote((string)$value);
                }
                $batch[] = '(' . implode(',', $vals) . ')';
                if (count($batch) >= 100) {
                    $this->writeInsertBatch($fh, $quoted, $columns ?: [], $batch);
                    $batch = [];
                }
            }
            if ($batch) $this->writeInsertBatch($fh, $quoted, $columns ?: [], $batch);
            fwrite($fh, "\n");
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
        if (!@is_file($out) || filesize($out) === 0) throw new \RuntimeException('PDO 数据库导出失败');
    }

    private function writeInsertBatch($fh, string $quotedTable, array $columns, array $batch): void
    {
        if (!$columns || !$batch) return;
        $cols = array_map(static fn($c) => '`' . str_replace('`', '``', (string)$c) . '`', $columns);
        fwrite($fh, 'INSERT INTO ' . $quotedTable . ' (' . implode(',', $cols) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
    }

    private function directorySize(string $dir): int
    {
        $size = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $path = str_replace($this->root . '/', '', $file->getPathname());
            if (str_starts_with($path, 'storage/migration-backups/')) continue;
            if ($file->isFile()) $size += $file->getSize();
        }
        return $size;
    }

    private function log(string $message): void
    {
        $file = $this->root . '/storage/migration-backups/migration.log';
        @mkdir(dirname($file), 0750, true);
        @file_put_contents($file, '[' . date('Y-m-d H:i:s') . '] ' . $message . "
", FILE_APPEND | LOCK_EX);
        @chmod($file, 0660);
    }

    private function restoreReadme(string $token): string
    {
        return "# Clay 官方站迁移包\n\n" .
            "1. 将本 ZIP 解压到新服务器站点目录。\n" .
            "2. 配置 Web 根目录指向该目录。\n" .
            "3. 首次访问 `/index.php?path=migration-setup`。\n" .
            "4. 输入迁移令牌并填写新数据库连接信息。\n" .
            "5. 提交成功后迁移入口会永久锁定。\n\n" .
            "迁移令牌：`{$token}`\n";
    }
}
