<?php

namespace App\Controllers;

use App\Core\Database;
use App\Middleware\AdminAuth;
use App\Models\PackageModel;
use App\Services\KeyManager;
use App\Services\PackageSigner;

class AdminPublishController
{
    public function index(): void
    {
        (new AdminFullpackController())->index();
    }

    public function toggle(): void
    {
        AdminAuth::check();
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'published'));
        (new PackageModel())->updateStatus($id, $status);
        redirect_or_ajax('/admin.php?path=publish&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
    }

    public function delete(): void
    {
        AdminAuth::check();
        csrf_verify();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect_or_ajax('/admin.php?path=publish&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
        }

        $model = new PackageModel();
        $pkg = $model->find($id);
        if (!$pkg) {
            redirect_or_ajax('/admin.php?path=publish&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
        }

        $this->deletePackageFiles($pkg, ($pkg['type'] ?? '') === 'full');
        $model->delete($id);

        redirect_or_ajax('/admin.php?path=publish&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
    }

    private function deletePackageFiles(array $pkg, bool $includeFull = false): void
    {
        $base = dirname(__DIR__, 2) . '/storage/packages/';
        $files = array_filter([
            (string) ($pkg['filename'] ?? ''),
            (string) ($pkg['rollback_filename'] ?? ''),
        ]);
        if ($includeFull) {
            $files[] = (string) ($pkg['full_filename'] ?? '');
        }

        foreach (array_unique($files) as $name) {
            $name = ltrim(str_replace(['..', '\\'], ['', '/'], $name), '/');
            if ($name === '') {
                continue;
            }
            $path = $base . $name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
