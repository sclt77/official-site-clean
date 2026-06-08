<?php

namespace App\Controllers;

use App\Middleware\AdminAuth;
use App\Services\SiteMigrationService;

class AdminMigrationController
{
    public function index(): void
    {
        AdminAuth::check();
        $service = new SiteMigrationService();
        $error = '';
        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            @ignore_user_abort(true);
            @set_time_limit(0);
            @ini_set('memory_limit', '1024M');
            csrf_verify();
            $action = (string)($_POST['_action'] ?? '');
            try {
                if ($action === 'create') {
                    if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
                        @session_write_close();
                    }
                    $pkg = $service->createPackage(trim((string)($_POST['mysqldump_path'] ?? '')) ?: null);
                    if (session_status() === PHP_SESSION_NONE) {
                        @session_start();
                    }
                    if (empty($pkg['path']) || !is_file((string)$pkg['path'])) {
                        throw new \RuntimeException('迁移包生成后未在目标目录找到，请检查 storage/migration-backups 权限或磁盘空间。');
                    }
                    $_SESSION['flash_migration'] = '迁移包已生成：' . $pkg['name'] . '；迁移令牌：' . $pkg['token'];
                    $_SESSION['flash_migration_latest'] = $pkg;
                    header('Location: /admin.php?path=migration'); exit;
                }
                if ($action === 'delete') {
                    $service->deletePackage((string)($_POST['name'] ?? ''));
                    $_SESSION['flash_migration'] = '迁移包已删除';
                    header('Location: /admin.php?path=migration'); exit;
                }
            } catch (\Throwable $e) {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $error = $e->getMessage();
                $_SESSION['flash_migration_error'] = $error;
                header('Location: /admin.php?path=migration'); exit;
            }
        }
        $message = (string)($_SESSION['flash_migration'] ?? '');
        $error = $error ?: (string)($_SESSION['flash_migration_error'] ?? '');
        unset($_SESSION['flash_migration'], $_SESSION['flash_migration_error']);
        $packages = $service->listPackages();
        $mysqldumpPath = trim((string)($_GET['mysqldump_path'] ?? ''));
        $preflight = $service->preflight($mysqldumpPath ?: null);
        $status = $service->status();
        $latestGenerated = $_SESSION['flash_migration_latest'] ?? null;
        unset($_SESSION['flash_migration_latest']);
        foreach ($packages as &$pkg) {
            try { $pkg['manifest'] = $service->packageManifest((string)$pkg['name']); } catch (\Throwable $e) { $pkg['manifest'] = []; }
        }
        unset($pkg);
        require dirname(__DIR__) . '/views/admin/migration.php';
    }

    public function download(): void
    {
        AdminAuth::check();
        $path = (new SiteMigrationService())->packagePath((string)($_GET['name'] ?? ''));
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
