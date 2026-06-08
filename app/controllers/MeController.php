<?php

namespace App\Controllers;

use App\Core\Database;
use App\Middleware\UserAuth;
use App\Models\SiteModel;
use App\Models\SiteLimitRequestModel;
use App\Models\MarketModel;
use App\Models\UserModel;
use App\Models\SettingModel;
use App\Services\AlipayFacePayService;

class MeController
{
    public function index(): void
    {
        UserAuth::check();
        $user = (new UserModel())->find((int)$_SESSION['auth_user']['id']) ?: $_SESSION['auth_user'];
        $_SESSION['auth_user'] = $user;
        $db = Database::connection();

        $siteCount = $db->prepare('SELECT product, COUNT(*) AS cnt FROM sites WHERE user_id=:user_id GROUP BY product');
        $siteCount->execute([':user_id' => (int) $user['id']]);
        $siteCounts = ['claybbs' => 0, 'cutot' => 0];
        foreach (($siteCount->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $row) {
            $prod = $this->normalizeProduct((string)($row['product'] ?? 'claybbs'));
            $siteCounts[$prod] = (int)($row['cnt'] ?? 0);
        }

        $downloadCount = $db->prepare('SELECT COUNT(*) FROM download_logs WHERE user_id=:user_id');
        $downloadCount->execute([':user_id' => (int) $user['id']]);

        $stats = [
            'sites' => $siteCounts['claybbs'] + $siteCounts['cutot'],
            'claybbs_sites' => $siteCounts['claybbs'],
            'cutot_sites' => $siteCounts['cutot'],
            'downloads' => (int) $downloadCount->fetchColumn(),
        ];

        $marketModel = new MarketModel();
        $publishedPlugins = $marketModel->allByDeveloperPublic((int)$user['id'], 'plugin');
        $publishedThemes = $marketModel->allByDeveloperPublic((int)$user['id'], 'theme');
        require dirname(__DIR__) . '/views/me/index.php';
    }


    public function profile(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = (new UserModel())->find($id);
        if (!$user) { http_response_code(404); exit('用户不存在'); }
        $marketModel = new MarketModel();
        $publishedPlugins = $marketModel->allByDeveloperPublic($id, 'plugin');
        $publishedThemes = $marketModel->allByDeveloperPublic($id, 'theme');
        require dirname(__DIR__) . '/views/me/public.php';
    }

    public function editProfile(): void
    {
        UserAuth::check();
        $userModel = new UserModel();
        $user = $userModel->find((int)$_SESSION['auth_user']['id']) ?: $_SESSION['auth_user'];
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            try {
                $avatar = $this->uploadProfileImage('avatar') ?: (string)($user['avatar'] ?? '');
                $cover = $this->uploadProfileImage('cover') ?: (string)($user['cover'] ?? '');
                $userModel->updateProfile((int)$user['id'], ['name'=>trim((string)($_POST['name'] ?? '')), 'bio'=>(string)($_POST['bio'] ?? ''), 'avatar'=>$avatar, 'cover'=>$cover]);
                $_SESSION['auth_user'] = $userModel->find((int)$user['id']) ?: $user;
                header('Location: /index.php?path=me'); exit;
            } catch (\Throwable $e) { $error = '保存失败：' . $e->getMessage(); }
        }
        require dirname(__DIR__) . '/views/me/edit_profile.php';
    }

    private function uploadProfileImage(string $field): string
    {
        if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) return '';
        $size = (int)($_FILES[$field]['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) throw new \RuntimeException('图片不能超过 5MB');
        $info = @getimagesize((string)$_FILES[$field]['tmp_name']);
        if ($info === false) throw new \RuntimeException('上传文件不是有效图片');
        $mimeToExt = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        $mime = (string)($info['mime'] ?? '');
        if (!isset($mimeToExt[$mime])) throw new \RuntimeException('只支持 jpg、jpeg、png、gif、webp 图片');
        $ext = $mimeToExt[$mime];
        $dir = dirname(__DIR__, 2) . '/uploads/profiles'; @mkdir($dir, 0755, true);
        $name = $field . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name)) throw new \RuntimeException('图片上传失败');
        return '/uploads/profiles/' . $name;
    }

    public function sites(): void
    {
        UserAuth::check();
        $user = (new UserModel())->find((int)$_SESSION['auth_user']['id']) ?: $_SESSION['auth_user'];
        $_SESSION['auth_user'] = $user;
        $siteModel = new SiteModel();
        $product = $this->normalizeProduct((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs'));
        $productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
        $error = '';
        $success = '';

        $settings = (new SettingModel())->getSiteConfig();
        $unbindEnabled = !empty($settings['user_site_unbind_enabled']) && (string)$settings['user_site_unbind_enabled'] === '1';
        $settingPrefix = $product === 'cutot' ? 'cutot' : 'claybbs';
        $authPurchaseEnabled = !empty($settings[$settingPrefix . '_auth_purchase_enabled']) && (string)$settings[$settingPrefix . '_auth_purchase_enabled'] === '1';
        $authPurchasePrice = max(0, (float)($settings[$settingPrefix . '_auth_purchase_price'] ?? 0));
        $authPurchaseMax = max(1, (int)($settings[$settingPrefix . '_auth_purchase_max'] ?? 10));
        $siteLimitRequestEnabled = !empty($settings[$settingPrefix . '_site_limit_request_enabled']) && (string)$settings[$settingPrefix . '_site_limit_request_enabled'] === '1';
        $siteLimitRequestMax = max(1, (int)($settings[$settingPrefix . '_site_limit_request_max'] ?? 1));
        $siteLimitRequestModel = new SiteLimitRequestModel();
        $sites = $siteModel->allByUser((int) $user['id'], $product);
        $siteLimit = max(0, (int)($user[$product === 'cutot' ? 'cutot_site_limit' : 'claybbs_site_limit'] ?? 0));
        $siteCount = count($sites);
        $pendingSiteLimitRequest = $siteLimitRequestModel->pendingForUser((int)$user['id'], $product);
        $latestSiteLimitRequest = $siteLimitRequestModel->latestForUser((int)$user['id'], $product);
        $approvedSiteLimitRequest = $siteLimitRequestModel->approvedForUser((int)$user['id'], $product);
        $siteLimitOrders = array_values(array_filter($siteLimitRequestModel->ordersByUser((int)$user['id']), static fn($o) => (string)($o['product'] ?? 'claybbs') === $product));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = (string)($_POST['_action'] ?? 'bind');
            if ($action === 'unbind') {
                if (!$unbindEnabled) {
                    $error = '当前暂不允许用户自助解除授权绑定，请联系管理员处理。';
                } else {
                    try {
                        $siteModel->unbindByUser((int)($_POST['id'] ?? 0), (int)$user['id']);
                        $success = '授权绑定已解除，该站点的 site_id / token / license_key 已失效。';
                    } catch (\Throwable $e) {
                        $error = '解除绑定失败：' . $e->getMessage();
                    }
                }
            } elseif ($action === 'purchase_limit') {
                if (!$authPurchaseEnabled || $authPurchasePrice <= 0) {
                    $error = '当前暂不开放授权名额购买。';
                } else {
                    try {
                        $requestCount = max(1, (int)($_POST['requested_count'] ?? 1));
                        if ($requestCount > $authPurchaseMax) {
                            throw new \RuntimeException('单次最多可购买 ' . $authPurchaseMax . ' 个授权名额');
                        }
                        $order = $siteLimitRequestModel->createPurchaseOrder((int)$user['id'], $product, $requestCount, $authPurchasePrice);
                        header('Location: /index.php?path=me/site-limit-pay&order_no=' . urlencode((string)$order['order_no']) . '&product=' . urlencode($product));
                        exit;
                    } catch (\Throwable $e) {
                        $error = '创建授权购买订单失败：' . $e->getMessage();
                    }
                }
            } elseif ($action === 'request_limit') {
                if (!$siteLimitRequestEnabled) {
                    $error = '当前暂不开放授权名额申请。';
                } else {
                    try {
                        $requestCount = max(1, (int)($_POST['requested_count'] ?? 1));
                        if ($requestCount > $siteLimitRequestMax) {
                            throw new \RuntimeException('单次最多可申请 ' . $siteLimitRequestMax . ' 个授权名额');
                        }
                        $reason = trim((string)($_POST['reason'] ?? ''));
                        if ($reason === '' || mb_strlen($reason, 'UTF-8') < 6) {
                            throw new \RuntimeException('请填写不少于 6 个字的申请原因');
                        }
                        $siteLimitRequestModel->create((int)$user['id'], $product, $siteLimit, $siteCount, $requestCount, $reason);
                        $success = '授权名额申请已提交，请等待管理员审核。';
                        $tab = 'apply';
                    } catch (\Throwable $e) {
                        $error = '提交授权申请失败：' . $e->getMessage();
                    }
                }
            } else {
                $domain = $this->normalizeDomain((string) ($_POST['domain'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                if ($domain === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = '请填写有效域名和邮箱';
                } else {
                    try {
                        $siteModel->create((int) $user['id'], $domain, $email, $product);
                        $success = $productLabel . ' 授权已绑定，并已生成 site_id / token / license_key';
                    } catch (\Throwable $e) {
                        $error = '绑定失败：' . $e->getMessage();
                    }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pendingSiteLimitRequest = $siteLimitRequestModel->pendingForUser((int)$user['id'], $product);
            $latestSiteLimitRequest = $siteLimitRequestModel->latestForUser((int)$user['id'], $product);
            $approvedSiteLimitRequest = $siteLimitRequestModel->approvedForUser((int)$user['id'], $product);
        }
        require dirname(__DIR__) . '/views/me/sites.php';
    }


    public function siteLimitPay(): void
    {
        UserAuth::check();
        $user = $_SESSION['auth_user'];
        $model = new SiteLimitRequestModel();
        $orderNo = trim((string)($_GET['order_no'] ?? ''));
        $order = $model->findOrder($orderNo);
        if (!$order || (int)($order['user_id'] ?? 0) !== (int)$user['id']) { http_response_code(404); exit('订单不存在'); }
        $settings = (new SettingModel())->getSiteConfig();
        $payError = '';
        $qrCode = '';
        $orderProduct = $this->normalizeProduct((string)($order['product'] ?? 'claybbs'));
        $orderProductLabel = $orderProduct === 'cutot' ? 'CUTOT' : 'ClayBBS';
        $item = ['name' => $orderProductLabel . ' 授权名额 x' . (int)($order['requested_count'] ?? 1)];
        if (($order['status'] ?? '') !== 'paid' && (string)($_GET['check'] ?? '') === '1') {
            try {
                $query = (new AlipayFacePayService($settings))->query((string)$order['order_no']);
                if (($query['ok'] ?? false) && in_array((string)($query['trade_status'] ?? ''), ['TRADE_SUCCESS','TRADE_FINISHED'], true)) {
                    $notifyMoney = round((float)($query['total_amount'] ?? 0), 2);
                    if (abs($notifyMoney - round((float)$order['amount'], 2)) <= 0.001) {
                        $order = $model->markOrderPaid((string)$order['order_no'], (string)($query['trade_no'] ?? '')) ?: $order;
                    } else {
                        $payError = '支付宝查单金额与订单金额不一致，请联系管理员核对。';
                    }
                } else {
                    $payError = '暂未查询到支付成功状态，请稍后再刷新。' . (!empty($query['message']) ? '（' . $query['message'] . '）' : '');
                }
            } catch (\Throwable $e) { $payError = '查单失败：' . $e->getMessage(); }
        }
        if (($order['status'] ?? '') !== 'paid') {
            try {
                $prepay = (new AlipayFacePayService($settings))->precreate($order, $item);
                $qrCode = (string)($prepay['qr_code'] ?? '');
            } catch (\Throwable $e) { if ($payError === '') $payError = $e->getMessage(); }
        }
        require dirname(__DIR__) . '/views/me/site_limit_pay.php';
    }


    public function clayguardLicense(): void
    {
        UserAuth::check();
        $user = (new UserModel())->find((int)$_SESSION['auth_user']['id']) ?: $_SESSION['auth_user'];
        $site = (new SiteModel())->findByIdForUser((int)($_GET['id'] ?? 0), (int)$user['id']);
        if (!$site) {
            http_response_code(404);
            exit('授权不存在');
        }
        if (($site['product'] ?? 'claybbs') !== 'claybbs') {
            http_response_code(403);
            exit('CUTOT 授权不使用 ClayGuard 授权文件');
        }
        if (($site['status'] ?? 'active') !== 'active' || ($site['license_status'] ?? 'active') !== 'active') {
            http_response_code(403);
            exit('授权未启用');
        }

        $license = $this->clayguardLicensePayload($site);
        if (empty($license['signature'])) {
            http_response_code(500);
            exit('ClayGuard 签名密钥不可用');
        }

        $json = json_encode($license, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            http_response_code(500);
            exit('授权文件生成失败');
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="clayguard.lic"');
        header('X-Content-Type-Options: nosniff');
        echo $json . "\n";
        exit;
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
        return (string)json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

    public function downloads(): void
    {
        UserAuth::check();
        $user = (new UserModel())->find((int)$_SESSION['auth_user']['id']) ?: $_SESSION['auth_user'];
        $_SESSION['auth_user'] = $user;
        $db = Database::connection();
        $product = $this->normalizeProduct((string)($_GET['product'] ?? 'claybbs'));
        $logs = $db->prepare(
            "SELECT dl.*, p.version, p.type, p.product
             FROM download_logs dl
             LEFT JOIN packages p ON p.id = dl.package_id
             WHERE dl.user_id = :user_id AND COALESCE(p.product, 'claybbs') = :product
             ORDER BY dl.id DESC LIMIT 100"
        );
        $logs->execute([':user_id' => (int) $user['id'], ':product' => $product]);
        $downloads = $logs->fetchAll();

        require dirname(__DIR__) . '/views/me/downloads.php';
    }

    public function keys(): void
    {
        UserAuth::check();
        require dirname(__DIR__) . '/views/me/keys.php';
    }

    public function market(): void
    {
        $marketTitle = '应用市场';
        $model = new MarketModel();
        $filterType = trim((string)($_GET['type'] ?? ''));
        if (!in_array($filterType, ['plugin', 'theme'], true)) $filterType = '';
        $marketType = $filterType;
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $pluginCategories = $model->categories('plugin', true);
        $themeCategories = $model->categories('theme', true);
        $items = $model->all($filterType !== '' ? $filterType : null, true, $categoryId > 0 ? $categoryId : null);
        require dirname(__DIR__) . '/views/market/list.php';
    }

    public function marketDetail(): void
    {
        $model = new MarketModel();
        $id = (int)($_GET['id'] ?? 0);
        $item = $model->find($id);
        if (!$item || ($item['status'] ?? '') !== 'published') { http_response_code(404); exit('应用不存在'); }
        $galleryImages = $model->imagesByItem($id);
        $license = null;
        if (!empty($_SESSION['auth_user'])) $license = $model->licenseForUser((int)$_SESSION['auth_user']['id'], $id);
        require dirname(__DIR__) . '/views/market/detail.php';
    }

    public function marketAcquire(): void
    {
        UserAuth::check(); csrf_verify();
        $user = $_SESSION['auth_user'];
        $model = new MarketModel();
        $id = (int)($_POST['id'] ?? 0);
        $item = $model->find($id);
        if (!$item || ($item['status'] ?? '') !== 'published') { http_response_code(404); exit('应用不存在'); }
        if ($model->licenseForUser((int)$user['id'], $id)) {
            header('Location: /index.php?path=market/detail&id=' . $id); exit;
        }
        if ((float)($item['price'] ?? 0) <= 0) {
            $model->createLicense((int)$user['id'], $id);
            header('Location: /index.php?path=market/detail&id=' . $id); exit;
        }
        $order = $model->createOrder((int)$user['id'], $id);
        header('Location: /index.php?path=market/pay&order_no=' . urlencode((string)$order['order_no'])); exit;
    }

    public function marketPay(): void
    {
        UserAuth::check();
        $user = $_SESSION['auth_user'];
        $model = new MarketModel();
        $orderNo = trim((string)($_GET['order_no'] ?? ''));
        $order = $model->findOrderByNo($orderNo);
        if (!$order || (int)($order['user_id'] ?? 0) !== (int)$user['id']) { http_response_code(404); exit('订单不存在'); }
        $item = $model->find((int)$order['item_id']);
        if (!$item) { http_response_code(404); exit('应用不存在'); }
        $license = $model->licenseForUser((int)$user['id'], (int)$order['item_id']);
        $settings = (new SettingModel())->getSiteConfig();
        $payError = '';
        $qrCode = '';
        if (($order['status'] ?? '') !== 'paid' && (string)($_GET['check'] ?? '') === '1') {
            try {
                $query = (new AlipayFacePayService($settings))->query((string)$order['order_no']);
                if (($query['ok'] ?? false) && in_array((string)($query['trade_status'] ?? ''), ['TRADE_SUCCESS','TRADE_FINISHED'], true)) {
                    $notifyMoney = round((float)($query['total_amount'] ?? 0), 2);
                    if (abs($notifyMoney - round((float)$order['amount'], 2)) <= 0.001) {
                        $order = $model->markOrderPaid((string)$order['order_no'], (string)($query['trade_no'] ?? '')) ?: $order;
                        $license = $model->licenseForUser((int)$user['id'], (int)$order['item_id']);
                    } else {
                        $payError = '支付宝查单金额与订单金额不一致，请联系管理员核对。';
                    }
                } else {
                    $payError = '暂未查询到支付成功状态，请稍后再刷新。' . (!empty($query['message']) ? '（' . $query['message'] . '）' : '');
                }
            } catch (\Throwable $e) { $payError = '查单失败：' . $e->getMessage(); }
        }
        if (($order['status'] ?? '') === 'paid') {
            if (!$license) $license = $model->createLicense((int)$user['id'], (int)$order['item_id'], (int)$order['id']);
        } else {
            try {
                $prepay = (new AlipayFacePayService($settings))->precreate($order, $item);
                $qrCode = (string)($prepay['qr_code'] ?? '');
            } catch (\Throwable $e) { if ($payError === '') $payError = $e->getMessage(); }
        }
        require dirname(__DIR__) . '/views/market/pay.php';
    }

    public function purchases(): void
    {
        UserAuth::check();
        $user = $_SESSION['auth_user'];
        $model = new MarketModel();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = (string)($_POST['_action'] ?? '');
            $key = (string)($_POST['license_key'] ?? '');
            if ($action === 'bind') $model->bindLicense($key, (int)$user['id'], $this->normalizeDomain((string)($_POST['domain'] ?? '')));
            if ($action === 'unbind') $model->unbindLicense($key, (int)$user['id']);
            header('Location: /index.php?path=me/purchases'); exit;
        }
        $licenses = $model->licensesByUser((int)$user['id']);
        require dirname(__DIR__) . '/views/me/purchases.php';
    }


    public function orders(): void
    {
        UserAuth::check();
        $user = $_SESSION['auth_user'];
        $model = new MarketModel();
        $marketOrders = $model->ordersByUser((int)$user['id']);
        $developerOrders = $model->developerOrdersByUser((int)$user['id']);
        require dirname(__DIR__) . '/views/me/orders.php';
    }

    public function plugins(): void
    {
        $_GET['type'] = 'plugin';
        $this->market();
    }

    public function themes(): void
    {
        $_GET['type'] = 'theme';
        $this->market();
    }

    private function normalizeProduct(string $product): string
    {
        $product = strtolower(trim($product));
        return in_array($product, ['claybbs', 'cutot'], true) ? $product : 'claybbs';
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
        $domain = preg_replace('#:\d+$#', '', $domain) ?? $domain;
        $domain = trim($domain, ". \t\r\n/");

        if ($domain === '' || !preg_match('/^[a-z0-9.-]+$/', $domain)) {
            return '';
        }

        return $domain;
    }
}
