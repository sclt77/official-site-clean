<?php

namespace App\Controllers;

use App\Core\Database;
use App\Middleware\UserAuth;
use App\Models\FullKeyModel;
use App\Models\PackageModel;
use App\Models\SettingModel;

class DownloadController
{
    public function full(): void
    {
        UserAuth::check();
        $id = (int) ($_GET['id'] ?? 0);
        $pkg = (new PackageModel())->find($id);
        if (!$pkg || ($pkg['type'] ?? '') !== 'full' || ($pkg['status'] ?? '') !== 'published') {
            http_response_code(404);
            exit('not found');
        }
        $product = in_array((string)($pkg['product'] ?? 'claybbs'), ['claybbs','cutot'], true) ? (string)($pkg['product'] ?? 'claybbs') : 'claybbs';
        $this->requireBoundSite($product);
        $packageModel = new PackageModel();
        if (!$packageModel->isLatestPublishedFull((int)$pkg['id'], $product)) {
            http_response_code(403);
            exit('历史完整包不可下载，请下载最新版完整包。');
        }

        $filename = $pkg['full_filename'] ?: $pkg['filename'];
        $file = $this->packagePath($filename);
        if (!file_exists($file)) {
            http_response_code(404);
            exit('file missing');
        }

        $key = bin2hex(random_bytes(16));
        (new FullKeyModel())->create((int) $pkg['id'], $key);

        $tmpDir = dirname(__DIR__, 2) . '/storage/packages/tmp_dl_' . bin2hex(random_bytes(6));
        @mkdir($tmpDir, 0755, true);
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            http_response_code(500);
            exit('zip open failed');
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $manifest = $tmpDir . '/manifest.json';
        $meta = file_exists($manifest) ? json_decode(file_get_contents($manifest), true) : [];
        if (!is_array($meta)) {
            $meta = [];
        }
        $meta['full_key'] = $key;
        file_put_contents($manifest, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $outPrefix = $product === 'cutot' ? 'cutot' : 'claybbs';
        $out = $tmpDir . '/' . $outPrefix . '[' . $pkg['version'] . '].zip';
        $this->zipDir($tmpDir, $out, ['manifest.json']);

        Database::connection()->prepare(
            "INSERT INTO download_logs (user_id, package_id, kind, filename) VALUES (:user_id, :package_id, 'full', :filename)"
        )->execute([
            ':user_id' => $_SESSION['auth_user']['id'],
            ':package_id' => $pkg['id'],
            ':filename' => basename($out),
        ]);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($out) . '"');
        readfile($out);
        @unlink($out);
        $this->rrmdir($tmpDir);
        exit;
    }


    private function requireBoundSite(string $product = 'claybbs'): void
    {
        $product = in_array($product, ['claybbs','cutot'], true) ? $product : 'claybbs';
        $userId = (int)($_SESSION['auth_user']['id'] ?? 0);
        if ($userId <= 0 || !$this->userHasBoundSite($userId, $product)) {
            http_response_code(403);
            $site = (new SettingModel())->getSiteConfig();
            $messageTitle = '需要先绑定授权域名';
            $productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
            $message = $productLabel . ' 完整包下载仅对已绑定对应产品授权域名的用户开放。请先到“我的授权”绑定该产品域名，或联系管理员分配绑定数量。';
            $actionUrl = '/index.php?path=me/sites&product=' . urlencode($product);
            $actionText = '前往我的授权';
            require dirname(__DIR__) . '/views/home/access_required.php';
            exit;
        }
    }

    private function userHasBoundSite(int $userId, string $product): bool
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM sites WHERE user_id=:uid AND product=:product AND status='active' AND license_status='active'");
        $stmt->execute([':uid' => $userId, ':product' => $product]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function packagePath(string $filename): string
    {
        return dirname(__DIR__, 2) . '/storage/packages/' . ltrim($filename, '/\\');
    }

    private function zipDir(string $source, string $zipPath, array $skipNames = []): void
    {
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $source = realpath($source);
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $filePath = $file->getRealPath();
            $local = substr($filePath, strlen($source) + 1);
            if (in_array(str_replace('\\', '/', $local), $skipNames, true)) {
                continue;
            }
            $zip->addFile($filePath, $local);
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
