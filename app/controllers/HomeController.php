<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use App\Models\PackageModel;
use App\Models\SettingModel;
use App\Models\MarketModel;
use App\Core\Database;

class HomeController
{
    public function index(): void
    {
        $announcements = (new AnnouncementModel())->active();
        $site = (new SettingModel())->getSiteConfig();
        $packageModel = new PackageModel();
        $fullPackages = $packageModel->allPublished('full', 'claybbs');
        $diffPackages = $packageModel->allPublished('diff', 'claybbs');
        $latestFull = $fullPackages[0] ?? null;
        $latestDiff = $diffPackages[0] ?? null;
        $marketItems = array_slice((new MarketModel())->all(null, true, null), 0, 6);
        $stats = $this->homeStats();
        $pageTitle = $site['site_name'] ?? 'Clay官方站';
        require dirname(__DIR__) . '/views/layouts/main.php';
        require dirname(__DIR__) . '/views/home/index.php';
        require dirname(__DIR__) . '/views/layouts/footer.php';
    }

    public function versionTree(): void
    {
        $site = (new SettingModel())->getSiteConfig();
        $product = $this->normalizeProduct((string)($_GET['product'] ?? 'claybbs'));
        $productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
        $packageModel = new PackageModel();
        $fullPackages = $this->cleanPackageNotes($packageModel->allPublished('full', $product));
        $diffPackages = $this->cleanPackageNotes($packageModel->allPublished('diff', $product));
        $latestFullId = (int)($fullPackages[0]['id'] ?? 0);
        $treeData = $this->buildVersionTreeData($fullPackages, $diffPackages);
        require dirname(__DIR__) . '/views/home/version_tree.php';
    }

    public function fullHistory(): void
    {
        $this->versionTree();
    }

    public function diffHistory(): void
    {
        $this->versionTree();
    }


    private function homeStats(): array
    {
        $db = Database::connection();
        $safeCount = static function (string $sql) use ($db): int {
            try { return (int)$db->query($sql)->fetchColumn(); } catch (\Throwable $e) { return 0; }
        };
        return [
            'versions' => $safeCount("SELECT COUNT(*) FROM packages WHERE product='claybbs' AND type='full' AND status='published'"),
            'apps' => $safeCount("SELECT COUNT(*) FROM market_items WHERE status='published'"),
            'developers' => $safeCount("SELECT COUNT(DISTINCT developer_user_id) FROM market_items WHERE status='published' AND developer_user_id IS NOT NULL"),
            'sites' => $safeCount("SELECT COUNT(*) FROM sites WHERE product='claybbs' AND status='active'"),
        ];
    }

    private function buildVersionTreeData(array $fullPackages, array $diffPackages): array
    {
        $versions = [];
        foreach ($fullPackages as $p) {
            $version = (string)($p['version'] ?? '');
            if ($version === '') continue;
            $versions[$version]['version'] = $version;
            $versions[$version]['full'] = $p;
        }
        foreach ($diffPackages as $p) {
            $version = (string)($p['version'] ?? '');
            if ($version === '') continue;
            $versions[$version]['version'] = $version;
            $versions[$version]['diffs'][] = $p;
            $from = (string)($p['from_version'] ?? '');
            if ($from !== '') {
                $versions[$version]['parents'][$from] = $from;
                $versions[$from]['version'] = $from;
                $versions[$from]['children'][$version] = $version;
            }
        }
        uksort($versions, static fn($a, $b) => version_compare((string)$a, (string)$b));
        $nodes = [];
        $edges = [];
        foreach ($versions as $version => $node) {
            $full = $node['full'] ?? null;
            $diff = $node['diffs'][0] ?? null;
            $notes = (string)(($full['notes'] ?? '') ?: ($diff['notes'] ?? ''));
            $nodes[] = [
                'id' => $version,
                'version' => $version,
                'hasFull' => !empty($full),
                'hasDiff' => !empty($diff),
                'fullId' => (int)($full['id'] ?? 0),
                'isLatestFull' => !empty($full) && (int)($full['id'] ?? 0) === (int)($fullPackages[0]['id'] ?? 0),
                'createdAt' => (string)(($full['created_at'] ?? '') ?: ($diff['created_at'] ?? '')),
                'level' => (string)($diff['update_level'] ?? 'normal'),
                'force' => !empty($diff['force_update']),
                'notes' => $notes,
            ];
            foreach (($node['parents'] ?? []) as $parent) {
                $edges[] = ['from' => $parent, 'to' => $version];
            }
        }
        return ['nodes' => $nodes, 'edges' => $edges];
    }

    private function cleanPackageNotes(array $packages): array
    {
        foreach ($packages as &$package) {
            $package['notes'] = $this->publicNotes((string)($package['notes'] ?? ''));
        }
        unset($package);
        return $packages;
    }

    private function publicNotes(string $notes): string
    {
        $lines = preg_split('/\R/u', $notes) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, '自动对比完整包生成：')) {
                continue;
            }
            if (str_starts_with($line, 'rollback_sha256=')) {
                continue;
            }
            if (preg_match('/^(package|full|rollback)?_?sha256=/i', $line)) {
                continue;
            }
            $kept[] = $line;
        }
        return trim(implode("\n", $kept));
    }


    private function normalizeProduct(string $product): string
    {
        $product = strtolower(trim($product));
        return in_array($product, ['claybbs', 'cutot'], true) ? $product : 'claybbs';
    }

    private function requireBoundSite(): void
    {
        $user = $_SESSION['auth_user'] ?? null;
        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            header('Location: /index.php?path=login');
            exit;
        }
        $product = $this->normalizeProduct((string)($_GET['product'] ?? 'claybbs'));
        if (!$this->userHasBoundSite($userId, $product)) {
            http_response_code(403);
            $site = (new SettingModel())->getSiteConfig();
            $messageTitle = '需要先绑定授权域名';
            $productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
            $message = $productLabel . ' 完整包版本树和更新包版本树仅对已绑定对应产品授权域名的用户开放。请先到“我的授权”绑定域名，或联系管理员分配绑定数量。';
            $actionUrl = '/index.php?path=me/sites&product=' . urlencode($product);
            $actionText = '前往我的授权';
            require dirname(__DIR__) . '/views/home/access_required.php';
            exit;
        }
    }

    private function userHasBoundSite(int $userId, string $product = 'claybbs'): bool
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM sites WHERE user_id=:uid AND product=:product AND status='active' AND license_status='active'");
        $stmt->execute([':uid' => $userId, ':product' => $product]);
        return (int)$stmt->fetchColumn() > 0;
    }

}
