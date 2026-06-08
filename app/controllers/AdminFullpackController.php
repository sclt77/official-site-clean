<?php

namespace App\Controllers;

use App\Core\Database;
use App\Middleware\AdminAuth;
use App\Models\PackageModel;
use App\Services\KeyManager;
use App\Services\PackageSigner;

class AdminFullpackController
{
    public function index(): void
    {
        AdminAuth::check();
        $db = Database::connection();
        $error = '';
        $success = '';
        $product = $this->normalizeProduct((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs'));
        $productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
        $branch = $product === 'cutot' ? 'cutot' : 'main';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            try {
                $version = trim((string) ($_POST['version'] ?? ''));
                $notes = trim((string) ($_POST['notes'] ?? ''));
                $updateLevel = trim((string)($_POST['update_level'] ?? 'normal'));
                if (!in_array($updateLevel, ['normal', 'security', 'critical'], true)) { $updateLevel = 'normal'; }
                $forceUpdate = !empty($_POST['force_update']);
                $minVersion = trim((string)($_POST['min_version'] ?? ''));
                $maxVersion = trim((string)($_POST['max_version'] ?? ''));
                if ($version === '') {
                    throw new \RuntimeException('请输入版本号');
                }
                $existsStmt = $db->prepare("SELECT id FROM packages WHERE product=:product AND type='full' AND version=:version LIMIT 1");
                $existsStmt->execute([':version' => $version, ':product' => $product]);
                if ($existsStmt->fetchColumn()) {
                    throw new \RuntimeException('该版本完整包已存在，请先删除旧完整包或更换版本号');
                }
                if (empty($_FILES['package']) || !is_uploaded_file($_FILES['package']['tmp_name'])) {
                    throw new \RuntimeException('请上传完整包');
                }
                $file = $_FILES['package'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($ext !== 'zip') {
                    throw new \RuntimeException('完整包必须为 zip');
                }

                $outDir = dirname(__DIR__, 2) . '/storage/packages';
                if (!is_dir($outDir)) {
                    @mkdir($outDir, 0755, true);
                }

                $previousFull = $this->latestFullPackage($product);

                $fname = ($product === 'cutot' ? 'cutot[' : 'claybbs[') . $version . '].zip';
                $dest = $outDir . '/' . $fname;
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    throw new \RuntimeException('完整包保存失败');
                }
                if ($product === 'claybbs') {
                    $this->syncFullPackageVersion($dest, $version);
                    $packageCheck = $this->validateFullPackage($dest, $version);
                    if (!empty($packageCheck['errors'])) {
                        @unlink($dest);
                        throw new \RuntimeException('完整包验包未通过：' . implode('；', $packageCheck['errors']));
                    }
                }

                (new KeyManager())->ensureKeyPair();
                $hash = hash_file('sha256', $dest);
                $signature = (new PackageSigner())->signFile($dest);
                $fullManifest = $this->readZipManifest($dest);
                (new PackageModel())->create([
                    'product' => $product,
                    'type' => 'full',
                    'version' => $version,
                    'from_version' => null,
                    'branch' => $branch,
                    'filename' => $fname,
                    'full_filename' => $fname,
                    'hash' => $hash,
                    'signature' => $signature,
                    'package_hash' => $hash,
                    'package_signature' => $signature,
                    'full_hash' => $hash,
                    'full_signature' => $signature,
                    'manifest_json' => json_encode($fullManifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    'file_size' => is_file($dest) ? filesize($dest) : null,
                    'full_file_size' => is_file($dest) ? filesize($dest) : null,
                    'notes' => $notes,
                    'has_code' => 1,
                    'has_db' => 1,
                    'status' => 'published',
                    'update_level' => $updateLevel,
                    'force_update' => $forceUpdate,
                    'min_version' => $minVersion !== '' ? $minVersion : null,
                    'max_version' => $maxVersion !== '' ? $maxVersion : null,
                ]);

                $autoMessage = '';
                if ($previousFull && !empty($previousFull['full_filename'])) {
                    $diffExists = $db->prepare("SELECT id FROM packages WHERE product=:product AND type='diff' AND version=:version LIMIT 1");
                    $diffExists->execute([':version' => $version, ':product' => $product]);
                    if ($diffExists->fetchColumn()) {
                        $autoMessage = '，但该版本更新包已存在，未重复构建';
                    } else {
                        $prevPath = $outDir . '/' . $previousFull['full_filename'];
                        if (is_file($prevPath)) {
                            $fromVersion = (string)($previousFull['version'] ?? '上一版本');
                            $diff = $this->buildDiffPackage($prevPath, $dest, $version, $notes, $outDir, $fromVersion, $product);
                            $diffHash = hash_file('sha256', $diff['package']);
                            $diffSig = (new PackageSigner())->signFile($diff['package']);
                            $rollbackHash = $diff['rollback'] ? hash_file('sha256', $diff['rollback']) : '';
                            $rollbackSig = $diff['rollback'] ? (new PackageSigner())->signFile($diff['rollback']) : '';
                            (new PackageModel())->create([
                                'product' => $product,
                                'type' => 'diff',
                                'version' => $version,
                                'from_version' => $fromVersion,
                                'branch' => $branch,
                                'filename' => basename($diff['package']),
                                'rollback_filename' => $diff['rollback'] ? basename($diff['rollback']) : null,
                                'full_filename' => $fname,
                                'hash' => $diffHash,
                                'signature' => $diffSig,
                                'package_hash' => $diffHash,
                                'package_signature' => $diffSig,
                                'full_hash' => $hash,
                                'full_signature' => $signature,
                                'rollback_hash' => $rollbackHash ?: null,
                                'rollback_signature' => $rollbackSig ?: null,
                                'manifest_json' => json_encode($diff['manifest'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                                'file_size' => is_file($diff['package']) ? filesize($diff['package']) : null,
                                'full_file_size' => is_file($dest) ? filesize($dest) : null,
                                'rollback_file_size' => $diff['rollback'] && is_file($diff['rollback']) ? filesize($diff['rollback']) : null,
                                'notes' => $notes,
                                'has_code' => $diff['changed'] > 0 || $diff['deleted'] > 0,
                                'has_db' => $diff['db'] > 0,
                                'status' => 'published',
                                'update_level' => $updateLevel,
                                'force_update' => $forceUpdate,
                                'min_version' => $minVersion !== '' ? $minVersion : null,
                                'max_version' => $maxVersion !== '' ? $maxVersion : null,
                            ]);
                            $autoMessage = '，并已自动生成增量更新包（从 ' . $fromVersion . ' 到 ' . $version . '，变更 ' . $diff['changed'] . '，删除 ' . $diff['deleted'] . '，数据库 ' . $diff['db'] . '）';
                        }
                    }
                }

                $success = '完整包已上传并完成签名' . $autoMessage;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $stmtFulls = $db->prepare("SELECT * FROM packages WHERE product=:product AND type='full' ORDER BY id DESC LIMIT 200");
        $stmtFulls->execute([':product' => $product]);
        $fulls = $stmtFulls->fetchAll();
        $stmtDiffs = $db->prepare("SELECT * FROM packages WHERE product=:product AND type='diff' ORDER BY id DESC LIMIT 200");
        $stmtDiffs->execute([':product' => $product]);
        $diffs = $stmtDiffs->fetchAll();
        $packages = $fulls;
        require dirname(__DIR__) . '/views/admin/fullpacks.php';
    }

    public function view(): void
    {
        AdminAuth::check();
        $id = (int) ($_GET['id'] ?? 0);
        $pkg = (new PackageModel())->find($id);
        if (!$pkg || ($pkg['type'] ?? '') !== 'full') {
            http_response_code(404);
            exit('not found');
        }

        $file = dirname(__DIR__, 2) . '/storage/packages/' . ($pkg['full_filename'] ?: $pkg['filename']);
        if (!file_exists($file)) {
            http_response_code(404);
            exit('file missing');
        }

        $tmpDir = dirname(__DIR__, 2) . '/storage/packages/tmp_view_' . bin2hex(random_bytes(6));
        @mkdir($tmpDir, 0755, true);
        try {
            $this->extractZipSafe($file, $tmpDir);
        } catch (\Throwable $e) {
            $this->rrmdir($tmpDir);
            http_response_code(500);
            exit('zip open failed');
        }

        $tree = $this->listFiles($tmpDir);
        require dirname(__DIR__) . '/views/admin/fullpack_view.php';
        $this->rrmdir($tmpDir);
    }

    public function replace(): void
    {
        AdminAuth::check();
        csrf_verify();

        $id = (int) ($_POST['id'] ?? 0);
        $path = trim((string) ($_POST['path'] ?? ''));
        if ($id <= 0 || $path === '') {
            http_response_code(400);
            exit('bad request');
        }
        if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            http_response_code(400);
            exit('file missing');
        }

        $pkg = (new PackageModel())->find($id);
        if (!$pkg || ($pkg['type'] ?? '') !== 'full') {
            http_response_code(404);
            exit('not found');
        }

        $file = dirname(__DIR__, 2) . '/storage/packages/' . ($pkg['full_filename'] ?: $pkg['filename']);
        $tmpDir = dirname(__DIR__, 2) . '/storage/packages/tmp_edit_' . bin2hex(random_bytes(6));
        @mkdir($tmpDir, 0755, true);
        try {
            $this->extractZipSafe($file, $tmpDir);
        } catch (\Throwable $e) {
            $this->rrmdir($tmpDir);
            http_response_code(500);
            exit('zip open failed');
        }

        $target = $tmpDir . '/' . ltrim(str_replace('..', '', $path), '/\\');
        $dir = dirname($target);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $this->rrmdir($tmpDir);
            http_response_code(500);
            exit('replace failed');
        }

        $this->zipDir($tmpDir, $file);
        (new KeyManager())->ensureKeyPair();
        $hash = hash_file('sha256', $file);
        $signature = (new PackageSigner())->signFile($file);
        (new PackageModel())->updateSignatureAndHash($id, $signature, $hash);

        $this->rrmdir($tmpDir);
        header('Location: /admin.php?path=fullpacks/view&id=' . $id);
        exit;
    }

    public function delete(): void
    {
        AdminAuth::check();
        csrf_verify();

        $id = (int) ($_POST['id'] ?? 0);
        $model = new PackageModel();
        $pkg = $model->find($id);
        if (!$pkg || ($pkg['type'] ?? '') !== 'full') {
            redirect_or_ajax('/admin.php?path=fullpacks&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
        }

        $db = Database::connection();
        $fullName = (string) (($pkg['full_filename'] ?? '') ?: ($pkg['filename'] ?? ''));
        $version = (string) ($pkg['version'] ?? '');

        $product = $this->normalizeProduct((string)($pkg['product'] ?? 'claybbs'));
        $stmt = $db->prepare("SELECT * FROM packages WHERE product=:product AND type='diff' AND (version=:version OR full_filename=:full_filename)");
        $stmt->execute([':version' => $version, ':full_filename' => $fullName, ':product' => $product]);
        $diffs = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        foreach ($diffs as $diff) {
            $this->deletePackageFiles($diff, false);
            $model->delete((int) $diff['id']);
        }

        $this->deletePackageFiles($pkg, true);
        $model->delete($id);

        redirect_or_ajax('/admin.php?path=fullpacks&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
    }

    private function deletePackageFiles(array $pkg, bool $includeFull = false): void
    {
        $base = dirname(__DIR__, 2) . '/storage/packages/';
        $files = [(string) ($pkg['filename'] ?? ''), (string) ($pkg['rollback_filename'] ?? '')];
        if ($includeFull) {
            $files[] = (string) ($pkg['full_filename'] ?? '');
        }
        foreach (array_unique(array_filter($files)) as $name) {
            $name = ltrim(str_replace(['..', '\\'], ['', '/'], $name), '/');
            if ($name === '') continue;
            $path = $base . $name;
            if (is_file($path)) @unlink($path);
        }
    }

    private function normalizeProduct(string $product): string
    {
        $product = strtolower(trim($product));
        return in_array($product, ['claybbs', 'cutot'], true) ? $product : 'claybbs';
    }

    private function latestFullPackage(string $product = 'claybbs'): ?array
    {
        $product = $this->normalizeProduct($product);
        $stmt = Database::connection()->prepare("SELECT * FROM packages WHERE product=:product AND type='full' AND status='published' ORDER BY id DESC LIMIT 1");
        $stmt->execute([':product' => $product]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function buildDiffPackage(string $oldZip, string $newZip, string $version, string $notes, string $outDir, string $fromVersion = '', string $product = 'claybbs'): array
    {
        $product = $this->normalizeProduct($product);
        $isCutot = $product === 'cutot';
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('服务器未启用 ZipArchive，无法自动对比压缩包');
        }

        $oldDir = $outDir . '/tmp_old_' . bin2hex(random_bytes(5));
        $newDir = $outDir . '/tmp_new_' . bin2hex(random_bytes(5));
        $diffDir = $outDir . '/tmp_diff_' . bin2hex(random_bytes(5));
        $rollbackDir = $outDir . '/tmp_rollback_' . bin2hex(random_bytes(5));
        @mkdir($oldDir, 0755, true);
        @mkdir($newDir, 0755, true);
        @mkdir($diffDir . '/code', 0755, true);
        @mkdir($diffDir . '/migrations', 0755, true);
        @mkdir($rollbackDir . '/code', 0755, true);

        $this->extractZipSafe($oldZip, $oldDir);
        $this->extractZipSafe($newZip, $newDir);

        $oldMap = $this->fileHashMap($oldDir);
        $newMap = $this->fileHashMap($newDir);
        $changed = 0;
        $deleted = 0;
        $db = 0;
        $deleteList = [];
        $rollbackDeleteList = [];
        $manifestChanged = [];
        $manifestDeleted = [];

        foreach ($newMap as $rel => $hash) {
            if ($this->isProtectedRel($rel, $product)) {
                continue;
            }
            if (!isset($oldMap[$rel]) || $oldMap[$rel] !== $hash) {
                if ($this->isDatabaseUpdateRel($rel, $product)) {
                    $target = $diffDir . '/migrations/' . basename($rel);
                    $db++;
                } else {
                    $target = $diffDir . '/code/' . $rel;
                    $changed++;
                }
                if (!is_dir(dirname($target))) @mkdir(dirname($target), 0755, true);
                copy($newDir . '/' . $rel, $target);
                $manifestChanged[] = $rel;

                // rollback: restore old file if it existed; otherwise delete this new file on rollback
                if (isset($oldMap[$rel]) && is_file($oldDir . '/' . $rel) && !$this->isDatabaseUpdateRel($rel, $product)) {
                    $rb = $rollbackDir . '/code/' . $rel;
                    if (!is_dir(dirname($rb))) @mkdir(dirname($rb), 0755, true);
                    copy($oldDir . '/' . $rel, $rb);
                } elseif (!$this->isDatabaseUpdateRel($rel, $product)) {
                    $rollbackDeleteList[] = $rel;
                }
            }
        }

        foreach ($oldMap as $rel => $hash) {
            if ($this->isProtectedRel($rel, $product)) {
                continue;
            }
            if (!isset($newMap[$rel])) {
                $deleteList[] = $rel;
                $deleted++;
                $manifestDeleted[] = $rel;
                // rollback should restore deleted old file
                if (!$this->isDatabaseUpdateRel($rel, $product)) {
                    $rb = $rollbackDir . '/code/' . $rel;
                    if (!is_dir(dirname($rb))) @mkdir(dirname($rb), 0755, true);
                    copy($oldDir . '/' . $rel, $rb);
                }
            }
        }

        $autoMigration = $isCutot ? '' : $this->buildAutoInstallSqlMigration($oldDir . '/database/install.sql', $newDir . '/database/install.sql', $version);
        if (!$isCutot && $autoMigration !== '') {
            $autoName = 'auto_install_diff_' . preg_replace('/[^0-9A-Za-z_.-]+/', '_', $version) . '.sql';
            file_put_contents($diffDir . '/migrations/' . $autoName, $autoMigration);
            $db++;
        }

        $requiresDbPush = $isCutot && $this->cutotPrismaChanged($oldDir, $newDir);
        if ($requiresDbPush && $db === 0) {
            $db = 1;
        }
        $requiresRestart = $isCutot ? $this->cutotRequiresRestart($manifestChanged, $manifestDeleted, $requiresDbPush) : false;

        if ($deleteList) {
            file_put_contents($diffDir . '/code/_delete.list', implode("\n", $deleteList) . "\n");
        }
        if ($rollbackDeleteList) {
            file_put_contents($rollbackDir . '/code/_delete.list', implode("\n", $rollbackDeleteList) . "\n");
        }

        $manifest = [
            'product' => $product,
            'runtime' => $isCutot ? 'node' : 'php',
            'schema' => $isCutot ? 'prisma' : 'sql',
            'version' => $version,
            'from_version' => $fromVersion,
            'description' => $notes,
            'generated_at' => date('c'),
            'requiresRestart' => $requiresRestart,
            'requiresDbPush' => $requiresDbPush,
            'requiresPrismaGenerate' => $requiresDbPush,
            'changed' => $manifestChanged,
            'deleted' => $manifestDeleted,
            'protected' => $this->protectedManifestList($product),
            'database' => $this->listMigrationNames($diffDir . '/migrations'),
            'hashes' => $this->manifestHashes($diffDir),
        ];
        file_put_contents($diffDir . '/manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        file_put_contents($rollbackDir . '/manifest.json', json_encode(['product' => $product, 'runtime' => $isCutot ? 'node' : 'php', 'version' => $version, 'rollback' => true, 'generated_at' => date('c')], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $prefix = $isCutot ? 'cutot' : 'claybbs';
        $diffFile = $outDir . '/diff_' . $prefix . '[' . $version . '].zip';
        $rollbackFile = $outDir . '/rollback_' . $prefix . '[' . $version . '].zip';
        $this->zipDir($diffDir, $diffFile);
        $this->zipDir($rollbackDir, $rollbackFile);

        $this->rrmdir($oldDir);
        $this->rrmdir($newDir);
        $this->rrmdir($diffDir);
        $this->rrmdir($rollbackDir);

        return ['package' => $diffFile, 'rollback' => $rollbackFile, 'changed' => $changed, 'deleted' => $deleted, 'db' => $db, 'manifest' => $manifest];
    }

    private function syncFullPackageVersion(string $zipPath, string $version): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('服务器未启用 ZipArchive，无法写入完整包版本号');
        }

        $tmpDir = dirname($zipPath) . '/tmp_version_' . bin2hex(random_bytes(5));
        @mkdir($tmpDir, 0755, true);
        try {
            $this->extractZipSafe($zipPath, $tmpDir);

            $manifestPath = $tmpDir . '/manifest.json';
            $manifest = [];
            if (is_file($manifestPath)) {
                $decoded = json_decode((string) file_get_contents($manifestPath), true);
                if (is_array($decoded)) {
                    $manifest = $decoded;
                }
            }
            $manifest['version'] = $version;
            $manifest['generated_at'] = $manifest['generated_at'] ?? date('c');
            file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

            $appConfig = $tmpDir . '/config/app.php';
            if (is_file($appConfig)) {
                $this->replacePhpArrayValue($appConfig, 'version', $version);
            }

            $updateConfig = $tmpDir . '/config/update-center.php';
            if (is_file($updateConfig)) {
                $this->replacePhpArrayValue($updateConfig, 'current_version', $version);
            }

            $this->zipDir($tmpDir, $zipPath);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->rrmdir($tmpDir);
        }
    }

    private function replacePhpArrayValue(string $file, string $key, string $value): void
    {
        $content = (string) file_get_contents($file);
        $quotedKey = preg_quote($key, '#');
        $replacement = "'" . $key . "' => '" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";

        $patterns = [
            "#'{$quotedKey}'\\s*=>\\s*'[^']*'#",
            '#"' . $quotedKey . '"\\s*=>\\s*"[^"]*"#',
            "#'{$quotedKey}'\\s*=>\\s*\"[^\"]*\"#",
            "#\"{$quotedKey}\"\\s*=>\\s*'[^']*'#",
        ];
        foreach ($patterns as $pattern) {
            $new = preg_replace($pattern, $replacement, $content, 1, $count);
            if ($count > 0 && is_string($new)) {
                file_put_contents($file, $new);
                return;
            }
        }

        $array = @include $file;
        if (is_array($array)) {
            $array[$key] = $value;
            file_put_contents($file, "<?php\n\nreturn " . var_export($array, true) . ";\n");
        }
    }

    private function validateFullPackage(string $zipPath, string $version): array
    {
        $errors = [];
        $warnings = [];
        if (!class_exists(\ZipArchive::class)) {
            return ['errors' => ['服务器未启用 ZipArchive'], 'warnings' => []];
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['errors' => ['无法打开 ZIP'], 'warnings' => []];
        }
        $required = ['index.php', 'admin.php', 'api.php', 'app/core/bootstrap.php', 'manifest.json'];
        $seen = [];
        $count = $zip->numFiles;
        if ($count > 6000) $warnings[] = '文件数量较多：' . $count;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));
            $seen[$name] = true;
            if (str_contains($name, '..') || str_starts_with($name, '/') || preg_match('#^[A-Za-z]:#', $name)) {
                $errors[] = '非法路径：' . $name;
            }
            foreach (['.env', 'config/database.php', 'install.lock'] as $forbid) {
                if ($name === $forbid) $errors[] = '不应包含敏感文件：' . $name;
            }
            foreach (['storage/uploads/', 'storage/logs/', 'storage/keys/'] as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    $relativeRuntimePath = substr($name, strlen($prefix));
                    $isDirectoryEntry = $relativeRuntimePath === '';
                    $isAllowedScaffold = in_array($relativeRuntimePath, ['.gitkeep', '.htaccess'], true);
                    if (!$isDirectoryEntry && !$isAllowedScaffold) {
                        $errors[] = '不应包含运行目录数据：' . $prefix;
                    }
                }
            }
            if (str_starts_with($name, '.git/')) $errors[] = '不应包含运行目录：.git/';
            if (preg_match('#(\.bak|\.tmp|\.old|~|\.DS_Store)$#i', $name)) {
                $warnings[] = '发现临时/备份文件：' . $name;
            }
        }
        foreach ($required as $file) {
            if (empty($seen[$file])) $errors[] = '缺少关键文件：' . $file;
        }
        $manifestRaw = $zip->getFromName('manifest.json');
        $zip->close();
        $manifest = $manifestRaw !== false ? json_decode((string)$manifestRaw, true) : null;
        if (!is_array($manifest)) {
            $errors[] = 'manifest.json 格式无效';
        } elseif ((string)($manifest['version'] ?? '') !== $version) {
            $errors[] = 'manifest.json 版本号与表单版本不一致';
        }
        return ['errors' => array_values(array_unique($errors)), 'warnings' => array_values(array_unique($warnings))];
    }


    private function buildAutoInstallSqlMigration(string $oldInstallSql, string $newInstallSql, string $version): string
    {
        if (!is_file($oldInstallSql) || !is_file($newInstallSql)) return '';
        if (hash_file('sha256', $oldInstallSql) === hash_file('sha256', $newInstallSql)) return '';

        $oldTables = $this->parseInstallSqlTables((string)file_get_contents($oldInstallSql));
        $newTables = $this->parseInstallSqlTables((string)file_get_contents($newInstallSql));
        $sql = [
            '-- Auto generated from database/install.sql diff',
            '-- Version: ' . $version,
            '-- Safe/idempotent migration. Existing data is protected: destructive changes are reported as comments only.',
            '',
        ];

        foreach ($newTables as $table => $newTable) {
            $safeTable = str_replace('`', '``', $table);
            if (!isset($oldTables[$table])) {
                $create = rtrim((string)($newTable['create'] ?? ''), "; \t\r
");
                if ($create !== '') {
                    $sql[] = $create . ';';
                    $sql[] = '';
                }
                continue;
            }

            $oldTable = $oldTables[$table];
            foreach (($newTable['columns'] ?? []) as $column => $definition) {
                $safeColumn = str_replace('`', '``', $column);
                if (!isset($oldTable['columns'][$column])) {
                    $sql[] = "SET @clay_col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$this->sqlString($table)}' AND COLUMN_NAME = '{$this->sqlString($column)}');";
                    $sql[] = "SET @clay_sql := IF(@clay_col_exists = 0, 'ALTER TABLE `{$safeTable}` ADD COLUMN " . $this->sqlString(rtrim($definition, ',')) . "', 'SELECT 1');";
                    $sql[] = 'PREPARE clay_stmt FROM @clay_sql; EXECUTE clay_stmt; DEALLOCATE PREPARE clay_stmt;';
                    continue;
                }
                if ($this->normalizeSqlDefinition((string)$oldTable['columns'][$column]) !== $this->normalizeSqlDefinition((string)$definition)) {
                    $sql[] = '-- Column definition changed: `' . $safeTable . '`.`' . $safeColumn . '`';
                    $sql[] = '-- Old: ' . (string)$oldTable['columns'][$column];
                    $sql[] = '-- New: ' . (string)$definition;
                    $sql[] = '-- Auto MODIFY is skipped to avoid unsafe data truncation/type conversion. Review manually if needed.';
                }
            }

            foreach (($newTable['keys'] ?? []) as $keyName => $definition) {
                if (!isset($oldTable['keys'][$keyName])) {
                    $safeKey = str_replace('`', '``', $keyName);
                    if ($keyName === 'PRIMARY') {
                        $sql[] = "SET @clay_key_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$this->sqlString($table)}' AND CONSTRAINT_TYPE = 'PRIMARY KEY');";
                    } else {
                        $sql[] = "SET @clay_key_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$this->sqlString($table)}' AND INDEX_NAME = '{$this->sqlString($keyName)}');";
                    }
                    $sql[] = "SET @clay_sql := IF(@clay_key_exists = 0, 'ALTER TABLE `{$safeTable}` ADD " . $this->sqlString(rtrim($definition, ',')) . "', 'SELECT 1');";
                    $sql[] = 'PREPARE clay_stmt FROM @clay_sql; EXECUTE clay_stmt; DEALLOCATE PREPARE clay_stmt;';
                }
            }

            foreach (($oldTable['columns'] ?? []) as $oldColumn => $_) {
                if (!isset($newTable['columns'][$oldColumn])) {
                    $sql[] = '-- Removed column detected but not dropped automatically for safety: `' . $safeTable . '`.`' . str_replace('`', '``', $oldColumn) . '`';
                }
            }
            foreach (($oldTable['keys'] ?? []) as $oldKey => $_) {
                if (!isset($newTable['keys'][$oldKey])) {
                    $sql[] = '-- Removed index detected but not dropped automatically for safety: `' . $safeTable . '`.`' . str_replace('`', '``', $oldKey) . '`';
                }
            }
            if (end($sql) !== '') $sql[] = '';
        }

        foreach ($oldTables as $oldTable => $_) {
            if (!isset($newTables[$oldTable])) {
                $sql[] = '-- Removed table detected but not dropped automatically for safety: `' . str_replace('`', '``', $oldTable) . '`';
            }
        }

        $body = trim(implode("
", $sql));
        $headerOnly = trim(implode("
", array_slice($sql, 0, 3)));
        return $body === $headerOnly ? '' : $body . "
";
    }

    private function sqlString(string $value): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }

    private function parseInstallSqlTables(string $sql): array
    {
        $tables = [];
        if (!preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`\s*\((.*?)\)\s*ENGINE\s*=.*?;/is', $sql, $matches, PREG_SET_ORDER)) return $tables;
        foreach ($matches as $m) {
            $table = (string)$m[1];
            $body = (string)$m[2];
            $columns = [];
            $keys = [];
            foreach ($this->splitSqlDefinitionList($body) as $line) {
                $def = trim($line);
                if ($def === '') continue;
                if (preg_match('/^`([^`]+)`\s+/s', $def, $cm)) { $columns[(string)$cm[1]] = $def; continue; }
                $keyName = null;
                if (preg_match('/^PRIMARY\s+KEY\b/is', $def)) $keyName = 'PRIMARY';
                elseif (preg_match('/^(?:UNIQUE\s+)?KEY\s+`([^`]+)`/is', $def, $km)) $keyName = (string)$km[1];
                if ($keyName !== null) $keys[$keyName] = $def;
            }
            $tables[$table] = ['create' => (string)$m[0], 'columns' => $columns, 'keys' => $keys];
        }
        return $tables;
    }

    private function splitSqlDefinitionList(string $body): array
    {
        $parts = []; $buf = ''; $depth = 0; $quote = ''; $len = strlen($body);
        for ($i = 0; $i < $len; $i++) {
            $ch = $body[$i]; $prev = $i > 0 ? $body[$i - 1] : '';
            if ($quote !== '') { $buf .= $ch; if ($ch === $quote && $prev !== '\\') $quote = ''; continue; }
            if ($ch === "'" || $ch === '"') { $quote = $ch; $buf .= $ch; continue; }
            if ($ch === '(') $depth++;
            if ($ch === ')' && $depth > 0) $depth--;
            if ($ch === ',' && $depth === 0) { $parts[] = $buf; $buf = ''; continue; }
            $buf .= $ch;
        }
        if (trim($buf) !== '') $parts[] = $buf;
        return $parts;
    }

    private function normalizeSqlDefinition(string $definition): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim(rtrim($definition, ','))) ?? '');
    }

    private function readZipManifest(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [];
        }
        $raw = $zip->getFromName('manifest.json');
        $zip->close();
        $data = $raw !== false ? json_decode((string)$raw, true) : [];
        return is_array($data) ? $data : [];
    }

    private function manifestHashes(string $base): array
    {
        $hashes = [];
        foreach (['code', 'migrations', 'manifest.json'] as $relBase) {
            $path = $base . '/' . $relBase;
            if (is_file($path)) {
                $hashes[$relBase] = hash_file('sha256', $path);
                continue;
            }
            if (!is_dir($path)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile()) continue;
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
                $hashes[$rel] = hash_file('sha256', $file->getPathname());
            }
        }
        ksort($hashes);
        return $hashes;
    }

    private function extractZipSafe(string $zipPath, string $to): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('无法打开完整包：' . basename($zipPath));
        }
        $root = realpath($to);
        if ($root === false) {
            $zip->close();
            throw new \RuntimeException('解压目录无效');
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string)$zip->getNameIndex($i));
            if ($name === '' || preg_match('/[\x00-\x1F\x7F]/', $name) || str_contains($name, '..') || str_starts_with($name, '/') || preg_match('#^[A-Za-z]:#', $name)) {
                $zip->close();
                throw new \RuntimeException('压缩包包含非法路径：' . $name);
            }
            if (substr($name, -1) === '/') {
                $dir = $root . '/' . rtrim($name, '/');
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $realDir = realpath($dir);
                if ($realDir === false || !$this->pathInside($realDir, $root)) {
                    $zip->close();
                    throw new \RuntimeException('压缩包目录越界：' . $name);
                }
                continue;
            }
            $target = $root . '/' . $name;
            $dir = dirname($target);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $realDir = realpath($dir);
            if ($realDir === false || !$this->pathInside($realDir, $root)) {
                $zip->close();
                throw new \RuntimeException('压缩包文件越界：' . $name);
            }
            if (is_link($target)) {
                $zip->close();
                throw new \RuntimeException('拒绝覆盖符号链接：' . $name);
            }
            $data = $zip->getFromIndex($i);
            if ($data === false) {
                $zip->close();
                throw new \RuntimeException('读取压缩包文件失败：' . $name);
            }
            if (file_put_contents($target, $data, LOCK_EX) === false) {
                $zip->close();
                throw new \RuntimeException('写入解压文件失败：' . $name);
            }
            @chmod($target, 0644);
        }
        $zip->close();
    }

    private function pathInside(string $path, string $root): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');
        return $path === $root || str_starts_with($path, $root . '/');
    }

    private function fileHashMap(string $base): array
    {
        $map = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
            $map[$rel] = hash_file('sha256', $file->getPathname());
        }
        ksort($map);
        return $map;
    }

    private function isDatabaseUpdateRel(string $rel, string $product = 'claybbs'): bool
    {
        $product = $this->normalizeProduct($product);
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        if ($product === 'cutot') {
            return $rel === 'prisma/schema.prisma';
        }
        if (preg_match('#(^|/)(migrations|database/update)/[^/]+\.sql$#i', $rel)) {
            return true;
        }
        // 论坛源码历史上把热更新迁移脚本直接放在 database/migrations_*.sql。
        // 构建增量包时必须识别并放入 /migrations 执行目录，否则老站点拿到更新包后只会复制文件，不会执行数据修复。
        return (bool) preg_match('#^database/migrations_[^/]+\.sql$#i', $rel);
    }

    private function isProtectedRel(string $rel, string $product = 'claybbs'): bool
    {
        $product = $this->normalizeProduct($product);
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        $protected = $product === 'cutot'
            ? ['.env', '.well-known', '.user.ini', 'uploads', 'storage', 'logs', 'node_modules', 'dist', 'build', 'runtime', 'tmp', 'cache']
            : ['config/database.php', 'config/update-center.php', '.env', '.well-known', 'storage/uploads', 'storage/certs', 'storage/keys', 'storage/logs', 'storage/packages'];
        foreach ($protected as $p) {
            if ($rel === $p || str_starts_with($rel, rtrim($p, '/') . '/')) {
                return true;
            }
        }
        return false;
    }

    private function protectedManifestList(string $product): array
    {
        $product = $this->normalizeProduct($product);
        if ($product === 'cutot') {
            return ['.env', '.user.ini', '.well-known', 'uploads', 'storage', 'logs', 'node_modules', 'dist', 'build', 'runtime', 'tmp', 'cache', 'user database'];
        }
        return ['config/database.php', 'config/update-center.php', 'storage/uploads', 'storage/certs', 'storage/keys', 'storage/logs', 'storage/packages', '.well-known'];
    }

    private function cutotPrismaChanged(string $oldDir, string $newDir): bool
    {
        $old = $oldDir . '/prisma/schema.prisma';
        $new = $newDir . '/prisma/schema.prisma';
        if (!is_file($new)) {
            return false;
        }
        if (!is_file($old)) {
            return true;
        }
        return hash_file('sha256', $old) !== hash_file('sha256', $new);
    }

    private function cutotRequiresRestart(array $changed, array $deleted, bool $requiresDbPush): bool
    {
        if ($requiresDbPush || $deleted) {
            return true;
        }
        foreach ($changed as $rel) {
            $rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
            if (preg_match('#^(apps/api|packages|prisma|package\.json|package-lock\.json|start-cutot\.sh|deploy\.sh|scripts/)#', $rel)) {
                return true;
            }
        }
        return false;
    }

    private function listMigrationNames(string $dir): array
    {
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/*.sql') ?: [];
        sort($files);
        return array_map('basename', $files);
    }

    private function listFiles(string $base): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $rel = str_replace($base . DIRECTORY_SEPARATOR, '', $file->getPathname());
            if ($file->isFile()) {
                $files[] = str_replace('\\', '/', $rel);
            }
        }
        sort($files);
        return $files;
    }

    private function zipDir(string $source, string $zipPath): void
    {
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $source = realpath($source);
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $filePath = $file->getRealPath();
            $local = substr($filePath, strlen($source) + 1);
            $zip->addFile($filePath, str_replace('\\', '/', $local));
        }
        $zip->close();
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($dir);
    }
}
