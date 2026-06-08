<?php

namespace App\Controllers;

use App\Core\Database;
use App\Models\FullKeyModel;
use App\Models\LicenseLogModel;
use App\Models\MarketModel;
use App\Models\PackageModel;
use App\Models\SiteModel;
use App\Models\SettingModel;
use App\Services\AlipayFacePayService;
use App\Services\KeyManager;
use App\Services\RateLimiter;

class ApiController
{

    private function verifySignedSiteRequest(array $input, array $site, string $action): bool
    {
        $ts = (int)($input['auth_ts'] ?? 0);
        $nonce = trim((string)($input['auth_nonce'] ?? ''));
        $sig = strtolower(trim((string)($input['auth_sig'] ?? '')));
        if ($ts <= 0 || abs(time() - $ts) > 300 || $nonce === '' || $sig === '') return false;
        if (($input['auth_action'] ?? '') !== $action) return false;
        $cacheDir = dirname(__DIR__, 2) . '/storage/auth-nonces';
        @mkdir($cacheDir, 0755, true);
        $nonceKey = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($site['site_id'] ?? '')) . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $nonce);
        $nonceFile = $cacheDir . '/' . $nonceKey;
        if (is_file($nonceFile)) return false;
        $payload = $input;
        unset($payload['auth_sig']);
        ksort($payload);
        $calc = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (string)($site['token'] ?? ''));
        if (!hash_equals($calc, $sig)) return false;
        file_put_contents($nonceFile, (string)time(), LOCK_EX);
        foreach (glob($cacheDir . '/*') ?: [] as $file) { if (@filemtime($file) < time() - 900) @unlink($file); }
        return true;
    }


    private function requireSignedSite(array $input, string $action, bool $json = true): ?array
    {
        $siteId = trim((string)($input['site_id'] ?? ''));
        $token = trim((string)($input['token'] ?? ''));
        $siteModel = new SiteModel();
        $site = $siteModel->findBySiteId($siteId);
        if (!$site || ($site['product'] ?? 'claybbs') !== 'claybbs' || ($site['token'] ?? '') !== $token || ($site['status'] ?? 'active') !== 'active') {
            if ($json) {
                $this->json(['ok' => false, 'error' => 'unauthorized']);
            } else {
                http_response_code(403);
                echo 'unauthorized';
            }
            return null;
        }
        if (!$this->verifySignedSiteRequest($input, $site, $action)) {
            if ($json) {
                $this->json(['ok' => false, 'error' => 'invalid signature']);
            } else {
                http_response_code(403);
                echo 'invalid signature';
            }
            return null;
        }
        (new SiteModel())->touch($siteId);
        return $site;
    }

    private function updateTicket(array $site, int $packageId, string $kind): string
    {
        $payload = [
            'site_id' => (string)$site['site_id'],
            'product' => (string)($site['product'] ?? 'claybbs'),
            'package_id' => $packageId,
            'kind' => $kind,
            'exp' => time() + 600,
            'nonce' => bin2hex(random_bytes(8)),
        ];
        $payload['sig'] = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (string)$site['token']);
        return rtrim(strtr(base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
    }

    private function verifyUpdateTicket(string $ticket, array $site, int $packageId, string $kind): bool
    {
        if ($ticket === '') return false;
        $json = base64_decode(strtr($ticket, '-_', '+/'));
        $data = json_decode((string)$json, true);
        if (!is_array($data)) return false;
        $sig = (string)($data['sig'] ?? ''); unset($data['sig']);
        if (($data['site_id'] ?? '') !== (string)$site['site_id'] || ($data['product'] ?? 'claybbs') !== (string)($site['product'] ?? 'claybbs') || (int)($data['package_id'] ?? 0) !== $packageId || ($data['kind'] ?? '') !== $kind || (int)($data['exp'] ?? 0) < time()) return false;
        $calc = hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (string)$site['token']);
        return hash_equals($calc, $sig);
    }

    public function checkUpdate(): void
    {
        // 频率限制：每 IP 每分钟最多 30 次
        $limiter = new RateLimiter(30, 60);
        if (!$limiter->check('check_update:' . $limiter->ip())) {
            http_response_code(429);
            $this->json(['ok' => false, 'error' => 'rate limit exceeded']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $siteId = trim((string) ($input['site_id'] ?? ''));
        $token = trim((string) ($input['token'] ?? ''));
        $version = trim((string) ($input['version'] ?? '0.0.0'));
        $branch = trim((string) ($input['branch'] ?? 'main'));

        $siteModel = new SiteModel();
        $site = $siteModel->findBySiteId($siteId);
        if (!$site || ($site['product'] ?? 'claybbs') !== 'claybbs' || ($site['token'] ?? '') !== $token || ($site['status'] ?? 'active') !== 'active') {
            $this->json(['ok' => false, 'error' => 'unauthorized']);
            return;
        }
        if (!$this->verifySignedSiteRequest($input, $site, 'check-update')) { $this->json(['ok'=>false,'error'=>'invalid signature']); return; }
        $siteModel->touch($siteId);

        $pkg = (new PackageModel())->latestDiff($version, $branch, 'claybbs');
        if (!$pkg) {
            $this->json(['ok' => true, 'update' => false]);
            return;
        }

        $pkgId = (int) $pkg['id'];
        $files = [
            'package' => [
                'filename' => (string)($pkg['filename'] ?? ''),
                'hash' => (string)(($pkg['package_hash'] ?? '') ?: ($pkg['hash'] ?? '')),
                'signature' => (string)(($pkg['package_signature'] ?? '') ?: ($pkg['signature'] ?? '')),
                'size' => isset($pkg['file_size']) ? (int)$pkg['file_size'] : null,
                'ticket' => $this->updateTicket($site, $pkgId, 'package'),
            ],
        ];
        if (!empty($pkg['full_filename'])) {
            $files['full'] = [
                'filename' => (string)$pkg['full_filename'],
                'hash' => (string)(($pkg['full_hash'] ?? '') ?: ($pkg['hash'] ?? '')),
                'signature' => (string)(($pkg['full_signature'] ?? '') ?: ($pkg['signature'] ?? '')),
                'size' => isset($pkg['full_file_size']) ? (int)$pkg['full_file_size'] : null,
                'ticket' => $this->updateTicket($site, $pkgId, 'full'),
            ];
        }
        if (!empty($pkg['rollback_filename'])) {
            $files['rollback'] = [
                'filename' => (string)$pkg['rollback_filename'],
                'hash' => (string)($pkg['rollback_hash'] ?? ''),
                'signature' => (string)($pkg['rollback_signature'] ?? ''),
                'size' => isset($pkg['rollback_file_size']) ? (int)$pkg['rollback_file_size'] : null,
                'ticket' => $this->updateTicket($site, $pkgId, 'rollback'),
            ];
        }
        $this->json([
            'ok' => true,
            'update' => true,
            'package' => [
                'id' => $pkgId,
                'to_version' => (string) $pkg['version'],
                'from_version' => (string)($pkg['from_version'] ?? ''),
                'branch' => (string) ($pkg['branch'] ?? $branch),
                'hash' => $files['package']['hash'],
                'signature' => $files['package']['signature'],
                'notes' => $this->publicPackageNotes((string) ($pkg['notes'] ?? '')),
                'filename' => (string) ($pkg['filename'] ?? ''),
                'has_code' => !empty($pkg['has_code']),
                'has_db' => !empty($pkg['has_db']),
                'has_rollback' => isset($files['rollback']),
                'rollback_file' => $pkg['rollback_filename'] ?: null,
                'full_file' => $pkg['full_filename'] ?: null,
                'full_key_used' => !empty($pkg['full_key_used']),
                'manifest' => json_decode((string)($pkg['manifest_json'] ?? '{}'), true) ?: [],
                'update_level' => (string)($pkg['update_level'] ?? 'normal'),
                'force_update' => !empty($pkg['force_update']),
                'min_version' => (string)($pkg['min_version'] ?? ''),
                'max_version' => (string)($pkg['max_version'] ?? ''),
                'ticket' => $files['package']['ticket'],
                'tickets' => array_map(static fn($f) => $f['ticket'] ?? '', $files),
                'files' => $files,
            ],
        ]);
    }


    private function publicPackageNotes(string $notes): string
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

    public function download(): void
    {
        // 频率限制：每 IP 每分钟最多 10 次
        $limiter = new RateLimiter(10, 60);
        if (!$limiter->check('download:' . $limiter->ip())) {
            http_response_code(429);
            $this->json(['ok' => false, 'error' => 'rate limit exceeded']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $siteId = trim((string) ($input['site_id'] ?? ''));
        $token = trim((string) ($input['token'] ?? ''));
        $packageId = (int) ($input['package_id'] ?? 0);
        $kind = trim((string) ($input['kind'] ?? 'package'));
        if (!in_array($kind, ['package', 'full', 'rollback'], true)) {
            http_response_code(400);
            exit('invalid kind');
        }
        $ticket = trim((string)($input['ticket'] ?? ''));

        $siteModel = new SiteModel();
        $site = $siteModel->findBySiteId($siteId);
        if (!$site || ($site['product'] ?? 'claybbs') !== 'claybbs' || ($site['token'] ?? '') !== $token || ($site['status'] ?? 'active') !== 'active') {
            http_response_code(403);
            exit('unauthorized');
        }
        if (!$this->verifySignedSiteRequest($input, $site, 'download')) { http_response_code(403); exit('invalid signature'); }
        if (!$this->verifyUpdateTicket($ticket, $site, $packageId, $kind)) { http_response_code(403); exit('invalid update ticket'); }
        $siteModel->touch($siteId);

        $pkg = (new PackageModel())->find($packageId);
        if (!$pkg || ($pkg['status'] ?? 'published') !== 'published' || ($pkg['product'] ?? 'claybbs') !== 'claybbs') {
            http_response_code(404);
            exit('not found');
        }

        if ($kind === 'full') {
            $filename = $pkg['full_filename'] ?: $pkg['filename'];
            $this->downloadFull($pkg, $siteId, $filename);
            return;
        }

        if ($kind === 'rollback') {
            $filename = $pkg['rollback_filename'] ?? '';
            if ($filename === '') {
                http_response_code(404);
                exit('rollback missing');
            }
            $this->streamFile($filename, $siteId, (int) $pkg['id'], 'rollback');
            return;
        }

        $this->streamFile((string) $pkg['filename'], $siteId, (int) $pkg['id'], 'package');
    }

    public function report(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $siteId = trim((string) ($input['site_id'] ?? ''));
        $token = trim((string) ($input['token'] ?? ''));
        $packageId = (int) ($input['package_id'] ?? 0);
        $status = trim((string) ($input['status'] ?? 'success'));
        $log = (string) ($input['log'] ?? '');
        $event = trim((string) ($input['event'] ?? ''));
        $fullKey = trim((string) ($input['full_key'] ?? ''));
        $fromVersion = trim((string)($input['from_version'] ?? ''));
        $toVersion = trim((string)($input['to_version'] ?? ''));
        $kind = trim((string)($input['kind'] ?? ''));
        $durationMs = (int)($input['duration_ms'] ?? 0);
        $healthJson = (string)($input['health_json'] ?? '');

        $siteModel = new SiteModel();
        $site = $siteModel->findBySiteId($siteId);
        if (!$site || ($site['product'] ?? 'claybbs') !== 'claybbs' || ($site['token'] ?? '') !== $token || ($site['status'] ?? 'active') !== 'active') {
            $this->json(['ok' => false, 'error' => 'unauthorized']);
            return;
        }
        if (!$this->verifySignedSiteRequest($input, $site, 'report')) { $this->json(['ok'=>false,'error'=>'invalid signature']); return; }
        $siteModel->touch($siteId);

        $db = Database::connection();
        $db->prepare(
            "INSERT INTO publish_logs (site_id, package_id, status, event, full_key, log, from_version, to_version, kind, duration_ms, health_json)
             VALUES (:site_id, :package_id, :status, :event, :full_key, :log, :from_version, :to_version, :kind, :duration_ms, :health_json)"
        )->execute([
            ':site_id' => $siteId,
            ':package_id' => $packageId,
            ':status' => substr($status, 0, 20),
            ':event' => $event !== '' ? substr($event, 0, 50) : null,
            ':full_key' => $fullKey !== '' ? substr($fullKey, 0, 120) : null,
            ':log' => mb_substr($log, 0, 20000),
            ':from_version' => $fromVersion !== '' ? substr($fromVersion, 0, 50) : null,
            ':to_version' => $toVersion !== '' ? substr($toVersion, 0, 50) : null,
            ':kind' => $kind !== '' ? substr($kind, 0, 20) : null,
            ':duration_ms' => $durationMs > 0 ? $durationMs : null,
            ':health_json' => $healthJson !== '' ? mb_substr($healthJson, 0, 20000) : null,
        ]);

        if ($event === 'full_key_used' && $fullKey !== '') {
            (new FullKeyModel())->markUsed($packageId, $fullKey, $siteId);
            (new PackageModel())->markFullKeyUsed($packageId, $siteId);
        }

        $this->json(['ok' => true]);
    }


    public function marketList(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $site = $this->requireSignedSite($input, 'market-list');
        if (!$site) return;
        $siteId = (string)$site['site_id'];
        $model = new MarketModel();
        $type = trim((string)($input['type'] ?? ''));
        if (!in_array($type, ['plugin','theme'], true)) { $type = ''; }
        $categoryId = (int)($input['category_id'] ?? 0);
        $acquired = $model->acquiredIds($siteId);
        $items = [];
        foreach ($model->all($type !== '' ? $type : null, true, $categoryId > 0 ? $categoryId : null) as $it) {
            $items[] = [
                'id'=>(int)$it['id'], 'type'=>(string)$it['type'], 'category_id'=>(int)($it['category_id'] ?? 0), 'category_name'=>(string)($it['category_name'] ?? ''), 'category_slug'=>(string)($it['category_slug'] ?? ''), 'slug'=>(string)$it['slug'], 'name'=>(string)$it['name'],
                'version'=>(string)$it['version'], 'description'=>(string)($it['description'] ?? ''), 'author'=>(string)($it['author'] ?? ''),
                'price'=>(float)$it['price'], 'hash'=>(string)$it['hash'], 'downloads'=>(int)$it['downloads'], 'acquired'=>in_array((int)$it['id'], $acquired, true),
                'manifest'=>json_decode((string)($it['manifest_json'] ?? '{}'), true) ?: [],
            ];
        }
        $this->json(['ok'=>true, 'items'=>$items]);
    }

    public function marketAcquire(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $itemId = (int)($input['item_id'] ?? 0);
        $site = $this->requireSignedSite($input, 'market-acquire');
        if (!$site) return;
        $siteId = (string)$site['site_id'];
        $model = new MarketModel();
        $item = $model->find($itemId);
        if (!$item || ($item['status'] ?? '') !== 'published') { $this->json(['ok'=>false,'error'=>'not_found']); return; }
        // 当前先支持免费获取；付费后续接订单支付后也写入 market_acquisitions。
        if ((float)$item['price'] > 0) { $this->json(['ok'=>false,'error'=>'payment_required']); return; }
        $model->acquire($siteId, $itemId, $site['user_id'] ?? null);
        $this->json(['ok'=>true]);
    }


    private function marketLicensePayload(array $site, array $item, array $license = []): array
    {
        return [
            'type' => (string)($item['type'] ?? ''),
            'slug' => (string)($item['slug'] ?? ''),
            'item_id' => (int)($item['id'] ?? 0),
            'license_key' => (string)($license['license_key'] ?? ''),
            'domain' => (string)($license['bound_domain'] ?? $site['domain'] ?? ''),
            'site_id' => (string)($site['site_id'] ?? ''),
            'user_id' => (int)($site['user_id'] ?? 0),
            'version' => (string)($item['version'] ?? ''),
            'issued_at' => time(),
            'nonce' => bin2hex(random_bytes(8)),
        ];
    }

    private function streamMarketPackageWithLicense(string $path, array $site, array $item, array $license = []): void
    {
        if ((float)($item['price'] ?? 0) <= 0) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            readfile($path);
            exit;
        }
        $payload = $this->marketLicensePayload($site, $item, $license);
        $sig = $this->signPayload($payload);
        if ($sig === '') { http_response_code(500); exit('license sign failed'); }
        $tmp = dirname(__DIR__, 2) . '/storage/market/tmp_auth_' . bin2hex(random_bytes(6)) . '.zip';
        @mkdir(dirname($tmp), 0755, true);
        if (!copy($path, $tmp)) { http_response_code(500); exit('package copy failed'); }
        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) { @unlink($tmp); http_response_code(500); exit('package open failed'); }
        $licenseJson = json_encode(['payload'=>$payload, 'sig'=>$sig], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $zip->addFromString('license.json', (string)$licenseJson);
        $zip->addFromString('market-license.json', (string)$licenseJson);
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    public function marketDownload(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $itemId = (int)($input['item_id'] ?? 0);
        $site = $this->requireSignedSite($input, 'market-download', false);
        if (!$site) exit;
        $siteId = (string)$site['site_id'];
        $model = new MarketModel();
        $item = $model->find($itemId);
        if (!$item || ($item['status'] ?? '') !== 'published') { http_response_code(404); exit('not found'); }
        if (!in_array($itemId, $model->acquiredIds($siteId), true)) {
            if ((float)($item['price'] ?? 0) > 0) { http_response_code(403); exit('not acquired'); }
            $model->acquire($siteId, $itemId, $site['user_id'] ?? null);
        }
        $license = [];
        if ((float)($item['price'] ?? 0) > 0) {
            $stmt = Database::connection()->prepare("SELECT * FROM market_licenses WHERE user_id=:uid AND item_id=:item AND status='active' LIMIT 1");
            $stmt->execute([':uid'=>(int)($site['user_id'] ?? 0), ':item'=>$itemId]);
            $license = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            $bound = $this->normalizeDomain((string)($license['bound_domain'] ?? ''));
            $siteDomain = $this->normalizeDomain((string)($site['domain'] ?? ''));
            if (!$license || $bound === '' || $bound !== $siteDomain) { http_response_code(403); exit('domain not bound'); }
        }
        $path = dirname(__DIR__, 2) . '/storage/market/' . basename((string)$item['filename']);
        if (!is_file($path)) { http_response_code(404); exit('file missing'); }
        $model->incrementDownload($itemId);
        $this->streamMarketPackageWithLicense($path, $site, $item, $license);
    }


    public function marketKeyDownload(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $key = trim((string)($input['key'] ?? ''));
        $site = $this->requireSignedSite($input, 'market-key-download', false);
        if (!$site) exit;
        $domain = $this->normalizeDomain((string)($input['domain'] ?? ($site['domain'] ?? '')));
        $siteDomain = $this->normalizeDomain((string)($site['domain'] ?? ''));
        if ($domain === '' || $siteDomain === '' || $domain !== $siteDomain) { http_response_code(403); exit('domain mismatch'); }
        $model = new MarketModel();
        $lic = $model->findLicense($key);
        if (!$lic || ($lic['status'] ?? '') !== 'active' || ($lic['item_status'] ?? '') !== 'published') { http_response_code(403); exit('invalid key'); }
        if ((int)($lic['user_id'] ?? 0) !== (int)($site['user_id'] ?? 0)) { http_response_code(403); exit('license owner mismatch'); }
        if ((float)($lic['price'] ?? 0) > 0) {
            $bound = $this->normalizeDomain((string)($lic['bound_domain'] ?? ''));
            if ($bound === '' || $bound !== $domain) { http_response_code(403); exit('domain not bound'); }
        }
        $path = dirname(__DIR__, 2) . '/storage/market/' . basename((string)$lic['filename']);
        if (!is_file($path)) { http_response_code(404); exit('file missing'); }
        $item = ['id'=>(int)$lic['item_id'], 'type'=>(string)$lic['type'], 'slug'=>(string)$lic['slug'], 'version'=>(string)$lic['version'], 'price'=>(float)$lic['price']];
        $model->incrementDownload((int)$lic['item_id']);
        $this->streamMarketPackageWithLicense($path, $site, $item, $lic);
    }

    public function marketPayNotify(): void
    {
        $params = array_merge($_GET ?: [], $_POST ?: []);
        $service = new AlipayFacePayService((new SettingModel())->getSiteConfig());
        if (!$service->verifyNotify($params)) { http_response_code(403); exit('fail'); }
        $orderNo = trim((string)($params['out_trade_no'] ?? ''));
        $tradeStatus = (string)($params['trade_status'] ?? '');
        $tradeNo = trim((string)($params['trade_no'] ?? ''));
        if ($orderNo === '') { http_response_code(400); exit('fail'); }
        if (!in_array($tradeStatus, ['TRADE_SUCCESS','TRADE_FINISHED'], true)) { exit('success'); }
        $model = new MarketModel();
        $order = $model->findOrderByNo($orderNo);
        $orderType = $order ? 'market' : '';
        if (!$order) { $order = $model->findDeveloperOrder($orderNo); $orderType = $order ? 'developer' : ''; }
        if (!$order) { $order = (new \App\Models\SiteLimitRequestModel())->findOrder($orderNo); $orderType = $order ? 'site_limit' : ''; }
        if (!$order) { http_response_code(404); exit('fail'); }
        $notifyMoney = isset($params['total_amount']) ? round((float)$params['total_amount'], 2) : round((float)$order['amount'], 2);
        if (abs($notifyMoney - round((float)$order['amount'], 2)) > 0.001) { http_response_code(400); exit('fail'); }
        if ($orderType === 'developer') $model->markDeveloperOrderPaid($orderNo, $tradeNo);
        elseif ($orderType === 'site_limit') (new \App\Models\SiteLimitRequestModel())->markOrderPaid($orderNo, $tradeNo);
        else $model->markOrderPaid($orderNo, $tradeNo);
        exit('success');
    }

    private function downloadFull(array $pkg, string $siteId, string $filename): void
    {
        $path = $this->packagePath($filename);
        if (!file_exists($path)) {
            http_response_code(404);
            exit('file missing');
        }

        $key = bin2hex(random_bytes(16));
        (new FullKeyModel())->create((int) $pkg['id'], $key);

        Database::connection()->prepare(
            "INSERT INTO download_logs (site_id, package_id, kind, filename) VALUES (:site_id, :package_id, 'full', :filename)"
        )->execute([
            ':site_id' => $siteId,
            ':package_id' => $pkg['id'],
            ':filename' => basename($filename),
        ]);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('X-Full-Key: ' . $key);
        readfile($path);
        exit;
    }

    private function streamFile(string $filename, string $siteId, int $packageId, string $kind): void
    {
        $path = $this->packagePath($filename);
        if (!file_exists($path)) {
            http_response_code(404);
            exit('file missing');
        }

        Database::connection()->prepare(
            "INSERT INTO download_logs (site_id, package_id, kind, filename) VALUES (:site_id, :package_id, :kind, :filename)"
        )->execute([
            ':site_id' => $siteId,
            ':package_id' => $packageId,
            ':kind' => $kind,
            ':filename' => basename($filename),
        ]);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        readfile($path);
        exit;
    }

    private function packagePath(string $filename): string
    {
        $name = basename(str_replace('\\', '/', $filename));
        if ($name === '' || str_contains($name, '..')) {
            return dirname(__DIR__, 2) . '/storage/packages/__invalid__';
        }
        return dirname(__DIR__, 2) . '/storage/packages/' . $name;
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



    public function cutotLicenseVerify(): void
    {
        $limiter = new RateLimiter(10, 60);
        if (!$limiter->check('cutot_license_verify:' . $limiter->ip())) { http_response_code(429); $this->json(['ok'=>false,'error'=>'rate limit exceeded']); return; }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $licenseKey = trim((string)($input['license_key'] ?? ''));
        $domain = $this->normalizeDomain((string)($input['domain'] ?? ''));
        $installId = trim((string)($input['install_id'] ?? ''));
        $machineHash = trim((string)($input['machine_hash'] ?? ''));
        $version = trim((string)($input['version'] ?? '0.0.0'));
        if ($licenseKey === '' || $domain === '' || $installId === '') { $this->json(['ok'=>false,'error'=>'missing_params']); return; }
        $siteModel = new SiteModel();
        $logModel = new LicenseLogModel();
        $site = $siteModel->findByLicenseKey($licenseKey, 'cutot');
        if (!$site) { $this->logLicense($logModel, 0, $domain, 'cutot_deny', 'invalid_key'); $this->json(['ok'=>false,'error'=>'invalid_key']); return; }
        if (($site['status'] ?? 'active') !== 'active' || ($site['license_status'] ?? 'active') !== 'active') { $this->logLicense($logModel, (int)$site['id'], $domain, 'cutot_deny', 'disabled'); $this->json(['ok'=>false,'error'=>'disabled']); return; }
        $boundDomain = $this->normalizeDomain((string)($site['domain'] ?? ''));
        if ($boundDomain === '' || $boundDomain !== $domain) { $this->logLicense($logModel, (int)$site['id'], $domain, 'cutot_deny', 'domain_mismatch'); $this->json(['ok'=>false,'error'=>'domain_mismatch']); return; }
        $siteModel->touch((string)$site['site_id']);
        $this->logLicense($logModel, (int)$site['id'], $domain, 'cutot_verify', 'ok version=' . $version . ' install=' . $installId);
        $payload = [
            'product' => 'CUTOT',
            'license_key' => (string)($site['license_key'] ?? ''),
            'domain' => (string)($site['domain'] ?? ''),
            'site_id' => (string)($site['site_id'] ?? ''),
            'token' => (string)($site['token'] ?? ''),
            'install_id' => $installId,
            'machine_hash' => $machineHash,
            'plan' => 'standard',
            'features' => ['core','hot_update'],
            'version' => $version,
            'issued_at' => time(),
            'expires_at' => time() + 365 * 86400,
            'ticket_expires_at' => time() + 86400,
            'nonce' => bin2hex(random_bytes(8)),
        ];
        $this->json(['ok'=>true,'payload'=>$payload,'sig'=>$this->signPayload($payload)]);
    }

    public function cutotUpdateCheck(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $site = $this->requireCutotSite($input, 'cutot-update-check');
        if (!$site) return;
        $version = trim((string)($input['version'] ?? '0.0.0'));
        $branch = trim((string)($input['branch'] ?? 'cutot')) ?: 'cutot';
        $pkg = (new PackageModel())->latestDiff($version, $branch, 'cutot');
        if (!$pkg) { $this->json(['ok'=>true,'update'=>false]); return; }
        $ticket = $this->updateTicket($site, (int)$pkg['id'], 'package');
        $this->json(['ok'=>true,'update'=>true,'package'=>[
            'id'=>(int)$pkg['id'], 'to_version'=>(string)$pkg['version'], 'from_version'=>(string)($pkg['from_version'] ?? ''), 'branch'=>$branch,
            'notes'=>$this->publicPackageNotes((string)($pkg['notes'] ?? '')), 'filename'=>(string)($pkg['filename'] ?? ''),
            'hash'=>(string)(($pkg['package_hash'] ?? '') ?: ($pkg['hash'] ?? '')), 'signature'=>(string)(($pkg['package_signature'] ?? '') ?: ($pkg['signature'] ?? '')),
            'has_code'=>!empty($pkg['has_code']), 'has_db'=>!empty($pkg['has_db']), 'force_update'=>!empty($pkg['force_update']),
            'update_level'=>(string)($pkg['update_level'] ?? 'normal'), 'manifest'=>json_decode((string)($pkg['manifest_json'] ?? '{}'), true) ?: [], 'ticket'=>$ticket,
        ]]);
    }

    public function cutotUpdateDownload(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $site = $this->requireCutotSite($input, 'cutot-update-download', false);
        if (!$site) exit;
        $packageId = (int)($input['package_id'] ?? 0);
        $ticket = trim((string)($input['ticket'] ?? ''));
        $pkg = (new PackageModel())->find($packageId);
        if (!$pkg || ($pkg['status'] ?? '') !== 'published' || ($pkg['product'] ?? '') !== 'cutot' || ($pkg['branch'] ?? '') !== 'cutot') { http_response_code(404); exit('package missing'); }
        if (!$this->verifyUpdateTicket($ticket, $site, $packageId, 'package')) { http_response_code(403); exit('invalid ticket'); }
        $this->streamFile((string)$pkg['filename'], (string)$site['site_id'], $packageId, 'cutot_package');
    }

    public function cutotUpdateReport(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $site = $this->requireCutotSite($input, 'cutot-update-report');
        if (!$site) return;
        $status = trim((string)($input['status'] ?? 'unknown'));
        $version = trim((string)($input['version'] ?? ''));
        $this->logLicense(new LicenseLogModel(), (int)$site['id'], (string)$site['domain'], 'cutot_update_report', $status . ' version=' . $version);
        $this->json(['ok'=>true]);
    }

    private function requireCutotSite(array $input, string $action, bool $json = true): ?array
    {
        $licenseKey = trim((string)($input['license_key'] ?? ''));
        $siteId = trim((string)($input['site_id'] ?? ''));
        $token = trim((string)($input['token'] ?? ''));
        $domain = $this->normalizeDomain((string)($input['domain'] ?? ''));
        $site = $siteId !== '' ? (new SiteModel())->findBySiteId($siteId) : (new SiteModel())->findByLicenseKey($licenseKey, 'cutot');
        if (!$site || ($site['product'] ?? 'claybbs') !== 'cutot' || ($site['token'] ?? '') !== $token || ($site['status'] ?? 'active') !== 'active' || ($site['license_status'] ?? 'active') !== 'active') {
            if ($json) $this->json(['ok'=>false,'error'=>'unauthorized']); else { http_response_code(403); echo 'unauthorized'; }
            return null;
        }
        if ($domain !== '' && $this->normalizeDomain((string)($site['domain'] ?? '')) !== $domain) {
            if ($json) $this->json(['ok'=>false,'error'=>'domain_mismatch']); else { http_response_code(403); echo 'domain mismatch'; }
            return null;
        }
        if (!$this->verifySignedSiteRequest($input, $site, $action)) {
            if ($json) $this->json(['ok'=>false,'error'=>'invalid signature']); else { http_response_code(403); echo 'invalid signature'; }
            return null;
        }
        (new SiteModel())->touch((string)$site['site_id']);
        return $site;
    }


    public function clayguardIssue(): void
    {
        $limiter = new RateLimiter(8, 60);
        if (!$limiter->check('clayguard_issue:' . $limiter->ip())) {
            http_response_code(429);
            $this->json(['ok' => false, 'error' => 'rate limit exceeded']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $licenseKey = trim((string)($input['license_key'] ?? ''));
        $domain = $this->normalizeDomain((string)($input['domain'] ?? ''));
        $installNonce = trim((string)($input['install_nonce'] ?? ''));

        if ($licenseKey === '' || $domain === '' || $installNonce === '') {
            $this->json(['ok' => false, 'error' => 'missing_params']);
            return;
        }

        $siteModel = new SiteModel();
        $logModel = new LicenseLogModel();
        $site = $siteModel->findByLicenseKey($licenseKey, 'claybbs');
        if (!$site) {
            $this->logLicense($logModel, 0, $domain, 'deny', 'invalid_key');
            $this->json(['ok' => false, 'error' => 'invalid_key']);
            return;
        }
        if (($site['status'] ?? 'active') !== 'active' || ($site['license_status'] ?? 'active') !== 'active') {
            $this->logLicense($logModel, (int)$site['id'], $domain, 'deny', 'disabled');
            $this->json(['ok' => false, 'error' => 'disabled']);
            return;
        }
        $boundDomain = $this->normalizeDomain((string)($site['domain'] ?? ''));
        if ($boundDomain === '' || $boundDomain !== $domain) {
            $this->logLicense($logModel, (int)$site['id'], $domain, 'deny', 'domain_mismatch');
            $this->json(['ok' => false, 'error' => 'domain_mismatch']);
            return;
        }

        $siteModel->touch((string)$site['site_id']);
        $this->logLicense($logModel, (int)$site['id'], $domain, 'clayguard_issue', 'ok');

        $license = $this->clayguardLicensePayload($site);
        $this->json([
            'ok' => true,
            'license' => $license,
            'filename' => 'clayguard.lic',
            'install_nonce' => $installNonce,
        ]);
    }

    private function clayguardLicensePayload(array $site): array
    {
        $expiresAt = '';
        foreach (['expires_at', 'license_expires_at', 'expire_at'] as $field) {
            if (!empty($site[$field])) {
                $expiresAt = date(DATE_ATOM, strtotime((string)$site[$field]));
                break;
            }
        }
        if ($expiresAt === '') {
            $expiresAt = date(DATE_ATOM, time() + 365 * 86400);
        }
        $license = [
            'product' => 'ClayBBS',
            'license_id' => (string)($site['license_key'] ?? ('LIC-' . strtoupper(bin2hex(random_bytes(6))))),
            'domain' => (string)($site['domain'] ?? ''),
            'site_id' => (string)($site['site_id'] ?? ''),
            'expires_at' => $expiresAt,
            'features' => ['core'],
            'issued_at' => date(DATE_ATOM),
        ];
        $license['signature'] = $this->signClayGuardPayload($license);
        return $license;
    }

    private function signClayGuardPayload(array $payload): string
    {
        $privatePath = dirname(__DIR__, 2) . '/storage/keys/clayguard-private.json';
        if (!is_file($privatePath)) {
            $privatePath = dirname(__DIR__, 2) . '/storage/clayguard/private.json';
        }
        if (!is_file($privatePath)) {
            $privatePath = '/www/wwwroot/ClayGuard/license/private.json';
        }
        if (!is_file($privatePath) || !function_exists('sodium_crypto_sign_detached')) {
            return '';
        }
        try {
            $data = json_decode((string)file_get_contents($privatePath), true, 512, JSON_THROW_ON_ERROR);
            $secret = $this->b64urlDecode((string)($data['secret_key'] ?? ''));
            $toSign = $payload;
            unset($toSign['signature']);
            return $this->b64urlEncode(sodium_crypto_sign_detached($this->canonicalJson($toSign), $secret));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function canonicalJson(array $data): string
    {
        $this->ksortRecursive($data);
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function ksortRecursive(array &$data): void
    {
        ksort($data);
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    private function b64urlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $pad = strlen($value) % 4;
        if ($pad) $value .= str_repeat('=', 4 - $pad);
        return (string)base64_decode($value, true);
    }

    public function publicKey(): void
    {
        try {
            $km = new KeyManager();
            $keys = $km->ensureKeyPair();
            $publicPath = (string) ($keys['public'] ?? '');
            $publicKey = $publicPath !== '' && is_file($publicPath) ? (string) file_get_contents($publicPath) : '';
            if ($publicKey === '' || !openssl_pkey_get_public($publicKey)) {
                $this->json(['ok' => false, 'error' => 'public_key_unavailable']);
                return;
            }
            $this->json(['ok' => true, 'public_key' => $publicKey]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => 'public_key_unavailable']);
        }
    }

    public function licenseActivate(): void
    {
        // 频率限制：每 IP 每分钟最多 10 次
        $limiter = new RateLimiter(10, 60);
        if (!$limiter->check('license_activate:' . $limiter->ip())) {
            http_response_code(429);
            $this->json(['ok' => false, 'error' => 'rate limit exceeded']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $licenseKey = trim((string) ($input['license_key'] ?? ''));
        $domain = $this->normalizeDomain((string) ($input['domain'] ?? ''));

        if ($licenseKey === '' || $domain === '') {
            $this->json(['ok' => false, 'error' => 'missing_params']);
            return;
        }

        $siteModel = new SiteModel();
        $logModel = new LicenseLogModel();
        $site = $siteModel->findByLicenseKey($licenseKey, 'claybbs');

        if (!$site) {
            $this->logLicense($logModel, 0, $domain, 'deny', 'invalid_key');
            $this->json(['ok' => false, 'error' => 'invalid_key']);
            return;
        }

        $licenseProblem = $this->licenseProblem($site);
        if ($licenseProblem !== '') {
            $this->logLicense($logModel, (int) $site['id'], $domain, 'deny', $licenseProblem);
            $this->json(['ok' => false, 'error' => $licenseProblem, 'payload' => $this->licensePayload($site, false)]);
            return;
        }

        $boundDomain = $this->normalizeDomain((string) ($site['domain'] ?? ''));
        if ($boundDomain === '' || $boundDomain !== $domain) {
            $this->logLicense($logModel, (int) $site['id'], $domain, 'deny', 'domain_mismatch');
            $this->json(['ok' => false, 'error' => 'domain_mismatch']);
            return;
        }

        $siteModel->touch((string) $site['site_id']);
        $this->logLicense($logModel, (int) $site['id'], $domain, 'activate', 'ok');

        $payload = $this->licensePayload($site, true);
        $this->json(['ok' => true, 'payload' => $payload, 'sig' => $this->signPayload($payload)]);
    }

    public function licenseVerify(): void
    {
        // 频率限制：每 IP 每分钟最多 10 次
        $limiter = new RateLimiter(10, 60);
        if (!$limiter->check('license_verify:' . $limiter->ip())) {
            http_response_code(429);
            $this->json(['ok' => false, 'error' => 'rate limit exceeded']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $licenseKey = trim((string) ($input['license_key'] ?? ''));
        $domain = $this->normalizeDomain((string) ($input['domain'] ?? ''));

        if ($licenseKey === '' || $domain === '') {
            $this->json(['ok' => false, 'error' => 'missing_params']);
            return;
        }

        $siteModel = new SiteModel();
        $logModel = new LicenseLogModel();
        $site = $siteModel->findByLicenseKey($licenseKey, 'claybbs');

        if (!$site) {
            $this->logLicense($logModel, 0, $domain, 'deny', 'invalid_key');
            $this->json(['ok' => false, 'error' => 'invalid_key']);
            return;
        }

        if (!empty($input['site_id']) && (string)$input['site_id'] !== (string)($site['site_id'] ?? '')) {
            $this->logLicense($logModel, (int) $site['id'], $domain, 'deny', 'site_id_mismatch');
            $this->json(['ok' => false, 'error' => 'site_id_mismatch']);
            return;
        }
        $signedLicenseRequest = !empty($input['auth_sig']);
        if ($signedLicenseRequest && !$this->verifySignedSiteRequest($input, $site, 'license-verify')) {
            $this->logLicense($logModel, (int) $site['id'], $domain, 'deny', 'invalid_challenge_signature');
            $this->json(['ok' => false, 'error' => 'invalid signature']);
            return;
        }

        $licenseProblem = $this->licenseProblem($site);
        if ($licenseProblem !== '') {
            $this->logLicense($logModel, (int) $site['id'], $domain, 'deny', $licenseProblem);
            $this->json(['ok' => false, 'error' => $licenseProblem, 'payload' => $this->licensePayload($site, false)]);
            return;
        }

        $boundDomain = $this->normalizeDomain((string) ($site['domain'] ?? ''));
        if ($boundDomain === '' || $boundDomain !== $domain) {
            $this->logLicense($logModel, (int) $site['id'], $domain, 'deny', 'domain_mismatch');
            $this->json(['ok' => false, 'error' => 'domain_mismatch']);
            return;
        }

        $siteModel->touch((string) $site['site_id']);
        $this->logLicense($logModel, (int) $site['id'], $domain, 'verify', 'ok');

        $includeToken = $signedLicenseRequest;
        $payload = $this->licensePayload($site, $includeToken);
        $this->json(['ok' => true, 'payload' => $payload, 'sig' => $this->signPayload($payload)]);
    }

    private function licensePayload(array $site, bool $includeToken = false): array
    {
        $payload = [
            'license_key' => (string) ($site['license_key'] ?? ''),
            'domain' => (string) ($site['domain'] ?? ''),
            'owner' => (string) ($site['user_name'] ?? $site['email'] ?? ''),
            'site_id' => (string) ($site['site_id'] ?? ''),
            'license_type' => (string)($site['license_type'] ?? 'permanent'),
            'license_status' => $this->effectiveLicenseStatus($site),
            'expires_at' => !empty($site['license_expires_at']) ? strtotime((string)$site['license_expires_at']) : 0,
            'remaining_seconds' => !empty($site['license_expires_at']) ? max(0, strtotime((string)$site['license_expires_at']) - time()) : 0,
            'trial_notice' => ((string)($site['license_type'] ?? 'permanent') === 'trial') ? '当前为 ClayBBS 体验授权，请购买正式授权。' : '',
            'issued_at' => time(),
            'bound_at' => !empty($site['bound_at']) ? strtotime((string) $site['bound_at']) : 0,
            'nonce' => bin2hex(random_bytes(8)),
        ];
        if ($includeToken) {
            $payload['token'] = (string) ($site['token'] ?? '');
        }
        return $payload;
    }

    private function licenseProblem(array $site): string
    {
        $status = $this->effectiveLicenseStatus($site);
        return $status === 'active' ? '' : $status;
    }

    private function effectiveLicenseStatus(array $site): string
    {
        $status = (string)($site['status'] ?? 'active');
        $licenseStatus = (string)($site['license_status'] ?? 'active');
        if ($status === 'locked' || $licenseStatus === 'locked') return 'locked';
        if ($status !== 'active') return 'disabled';
        if ($licenseStatus !== 'active' && $licenseStatus !== 'expired') return $licenseStatus;
        if ((string)($site['license_type'] ?? 'permanent') === 'trial') {
            $exp = !empty($site['license_expires_at']) ? strtotime((string)$site['license_expires_at']) : 0;
            if ($exp > 0 && $exp < time()) return 'expired';
        }
        if ($licenseStatus === 'expired') return 'expired';
        return 'active';
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') {
            return '';
        }
        if (str_contains($domain, '://')) {
            $host = (string) parse_url($domain, PHP_URL_HOST);
            $domain = $host !== '' ? $host : $domain;
        }
        $domain = preg_replace('#/.*$#', '', $domain) ?? $domain;
        $domain = preg_replace('#:\\d+$#', '', $domain) ?? $domain;
        $domain = trim($domain, ". \t\r\n/");
        if ($domain === '' || !preg_match('/^[a-z0-9.-]+$/', $domain)) {
            return '';
        }
        return $domain;
    }

    private function signPayload(array $payload): string
    {
        $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($data === false) {
            return '';
        }

        try {
            $km = new KeyManager();
            $keys = $km->ensureKeyPair();
            $privatePath = (string) ($keys['private'] ?? '');
            if ($privatePath === '' || !file_exists($privatePath)) {
                return '';
            }
            $privatePem = file_get_contents($privatePath) ?: '';
            $privateKey = openssl_pkey_get_private($privatePem);
            if (!$privateKey) {
                return '';
            }
            $signature = '';
            $ok = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            if (!$ok || $signature === '') {
                return '';
            }
            return base64_encode($signature);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function logLicense(LicenseLogModel $logModel, int $siteId, string $domain, string $action, string $detail): void
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $logModel->create($siteId, $domain, $ip, $action, $detail);
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
