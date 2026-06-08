<?php

namespace App\Controllers;

use App\Middleware\UserAuth;
use App\Models\MarketModel;
use App\Models\SettingModel;
use App\Services\AlipayFacePayService;

class DeveloperController
{
    public function index(): void
    {
        UserAuth::check();
        $user = $this->freshUser();
        if (!$this->isDeveloper($user)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_verify(); $joinType = (string)($_POST['join_type'] ?? 'paid'); $model = new MarketModel(); if ($joinType === 'public') { $model->createDeveloperApplication((int)$user['id'], (string)($_POST['reason'] ?? '')); header('Location: /index.php?path=developer'); exit; } $order = $model->developerJoinOrder((int)$user['id']); header('Location: /index.php?path=developer/join-pay&order_no=' . urlencode((string)$order['order_no'])); exit; }
            $settings = (new SettingModel())->getSiteConfig();
            $application = (new MarketModel())->developerApplicationForUser((int)$user['id']);
            require dirname(__DIR__) . '/views/developer/no_permission.php'; return;
        }
        $model = new MarketModel();
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            try {
                $action = (string)($_POST['_action'] ?? '');
                if ($action === 'create_app') $this->createApp($model, (int)$user['id']);
                if ($action === 'edit_app') $this->editApp($model, (int)$user['id']);
                if ($action === 'submit_version') $this->submitVersion($model, (int)$user['id']);
                if ($action === 'appeal_app') $this->appealApp($model, (int)$user['id']);
                if ($action === 'update_images') $this->updateImages($model, (int)$user['id']);
                if ($action === 'withdraw') $model->createWithdrawal((int)$user['id'], (float)($_POST['amount'] ?? 0), (string)($_POST['account_name'] ?? ''), (string)($_POST['account_no'] ?? ''));
                if ($action === 'upgrade_normal') { $order = $model->developerJoinOrder((int)$user['id']); header('Location: /index.php?path=developer/join-pay&order_no=' . urlencode((string)$order['order_no'])); exit; }
                header('Location: /index.php?path=developer'); exit;
            } catch (\Throwable $e) { $error = '操作失败：' . $e->getMessage(); }
        }
        $items = $model->allByDeveloper((int)$user['id']);
        $pluginCategories = $model->categories('plugin', true);
        $themeCategories = $model->categories('theme', true);
        $versions = [];
        foreach ($items as $it) $versions[(int)$it['id']] = $model->versionsByItem((int)$it['id']);
        $appeals = $model->appealsByDeveloper((int)$user['id']);
        $appealMap = $model->appealMapByDeveloper((int)$user['id']);
        $imageMap = $model->imageMapByItems(array_map(fn($it) => (int)$it['id'], $items));
        $sales = $model->developerSales((int)$user['id']);
        $balance = $model->developerBalance((int)$user['id']);
        $withdrawals = $model->withdrawals((int)$user['id']);
        $settings = (new SettingModel())->getSiteConfig();
        require dirname(__DIR__) . '/views/developer/index.php';
    }


    public function joinPay(): void
    {
        UserAuth::check();
        $user = $this->freshUser();
        if ($this->isDeveloper($user) && (string)($user['developer_level'] ?? '') !== 'public') { header('Location: /index.php?path=developer'); exit; }
        $model = new MarketModel();
        $settings = (new SettingModel())->getSiteConfig();
        $orderNo = trim((string)($_GET['order_no'] ?? ''));
        $order = $model->findDeveloperOrder($orderNo);
        if (!$order || (int)$order['user_id'] !== (int)$user['id']) { http_response_code(404); exit('订单不存在'); }
        $payError = ''; $qrCode = '';
        if (($order['status'] ?? '') !== 'paid' && (string)($_GET['check'] ?? '') === '1') {
            try {
                $query = (new AlipayFacePayService($settings))->query((string)$order['order_no']);
                if (($query['ok'] ?? false) && in_array((string)$query['trade_status'], ['TRADE_SUCCESS','TRADE_FINISHED'], true) && abs(round((float)$query['total_amount'],2)-round((float)$order['amount'],2))<=0.001) {
                    $order = $model->markDeveloperOrderPaid((string)$order['order_no'], (string)$query['trade_no']) ?: $order;
                    $_SESSION['auth_user']['role'] = 'developer'; $_SESSION['auth_user']['developer_level'] = 'normal';
                } else { $payError = '暂未查询到支付成功状态，请稍后再刷新。'; }
            } catch (\Throwable $e) { $payError = '查单失败：' . $e->getMessage(); }
        }
        if (($order['status'] ?? '') !== 'paid') {
            try { $prepay = (new AlipayFacePayService($settings))->precreate($order, ['name'=>'普通开发者权限']); $qrCode = (string)($prepay['qr_code'] ?? ''); }
            catch (\Throwable $e) { if ($payError==='') $payError = $e->getMessage(); }
        }
        require dirname(__DIR__) . '/views/developer/join_pay.php';
    }

    public function history(): void
    {
        UserAuth::check();
        $user = $this->freshUser();
        if (!$this->isDeveloper($user)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_verify(); $joinType = (string)($_POST['join_type'] ?? 'paid'); $model = new MarketModel(); if ($joinType === 'public') { $model->createDeveloperApplication((int)$user['id'], (string)($_POST['reason'] ?? '')); header('Location: /index.php?path=developer'); exit; } $order = $model->developerJoinOrder((int)$user['id']); header('Location: /index.php?path=developer/join-pay&order_no=' . urlencode((string)$order['order_no'])); exit; }
            $settings = (new SettingModel())->getSiteConfig();
            $application = (new MarketModel())->developerApplicationForUser((int)$user['id']);
            require dirname(__DIR__) . '/views/developer/no_permission.php'; return;
        }
        $model = new MarketModel();
        $itemId = (int)($_GET['id'] ?? 0);
        $app = $model->find($itemId);
        if (!$app || (int)($app['developer_user_id'] ?? 0) !== (int)$user['id']) {
            http_response_code(404);
            exit('应用不存在或无权限');
        }
        $versions = $model->versionsByItem($itemId);
        require dirname(__DIR__) . '/views/developer/history.php';
    }

    private function isDeveloper(array $user): bool { return in_array($user['role'] ?? 'user', ['developer','admin'], true); }

    private function freshUser(): array
    {
        $id = (int)(($_SESSION['auth_user']['id'] ?? 0));
        $user = $id > 0 ? (new \App\Models\UserModel())->find($id) : null;
        if ($user) { $_SESSION['auth_user'] = $user; return $user; }
        return $_SESSION['auth_user'] ?? [];
    }


    private function isPublicDeveloper(): bool
    {
        return (string)(($_SESSION['auth_user']['developer_level'] ?? 'none')) === 'public';
    }

    private function allowedPrice(float $price): float
    {
        if ($this->isPublicDeveloper() && $price > 0) {
            throw new \RuntimeException('公益开发者只能发布免费插件和主题；如需发布付费应用，请升级为普通开发者。');
        }
        return max(0, $price);
    }

    private function currentAuthor(): string
    {
        $u = $_SESSION['auth_user'] ?? [];
        $name = trim((string)($u['name'] ?? ''));
        if ($name !== '') return $name;
        $email = (string)($u['email'] ?? '');
        return $email !== '' ? preg_replace('/@.*$/', '', $email) : ('用户' . (string)($u['id'] ?? ''));
    }

    private function createApp(MarketModel $model, int $userId): void
    {
        $type = (string)($_POST['type'] ?? '');
        if (!in_array($type, ['plugin','theme'], true)) throw new \RuntimeException('请选择插件或主题');
        $slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_POST['slug'] ?? ''));
        if ($slug === '') throw new \RuntimeException('请填写英文 slug');
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') throw new \RuntimeException('请填写应用名称');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if (!$model->categoryForType($categoryId, $type, true)) throw new \RuntimeException('请选择有效分类');
        $logo = $this->uploadLogo();
        $price = $this->allowedPrice((float)($_POST['price'] ?? 0));
        $itemId = $model->createApp(['type'=>$type, 'category_id'=>$categoryId, 'slug'=>$slug, 'name'=>$name, 'description'=>(string)($_POST['description'] ?? ''), 'author'=>$this->currentAuthor(), 'price'=>$price, 'logo'=>$logo, 'developer_user_id'=>$userId]);
        $model->addImages($itemId, $userId, $this->uploadGalleryImages());
    }

    private function editApp(MarketModel $model, int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $model->find($id);
        if (!$item || (int)($item['developer_user_id'] ?? 0) !== $userId) throw new \RuntimeException('应用不存在或无权限');
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') throw new \RuntimeException('请填写应用名称');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if (!$model->categoryForType($categoryId, (string)$item['type'], true)) throw new \RuntimeException('请选择有效分类');
        $logo = $this->uploadLogo() ?: (string)($item['logo'] ?? '');
        $price = $this->allowedPrice((float)($_POST['price'] ?? 0));
        $model->updateApp($id, $userId, ['category_id'=>$categoryId, 'name'=>$name, 'description'=>(string)($_POST['description'] ?? ''), 'author'=>$this->currentAuthor(), 'price'=>$price, 'logo'=>$logo]);
    }

    private function appealApp(MarketModel $model, int $userId): void
    {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $reason = (string)($_POST['reason'] ?? '');
        $model->createAppeal($itemId, $userId, $reason);
    }

    private function updateImages(MarketModel $model, int $userId): void
    {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $deleted = $model->deleteImages($itemId, $userId, $_POST['delete_images'] ?? []);
        foreach ($deleted as $path) {
            $full = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
            $galleryRoots = [
                dirname(__DIR__, 2) . '/uploads/app-gallery',
                dirname(__DIR__, 2) . '/storage/uploads/app-gallery',
            ];
            $realDir = realpath(dirname($full)) ?: '';
            foreach ($galleryRoots as $galleryRoot) {
                if ($realDir !== '' && str_starts_with($realDir, $galleryRoot)) { @unlink($full); break; }
            }
        }
        $model->addImages($itemId, $userId, $this->uploadGalleryImages());
    }

    private function uploadLogo(): string
    {
        if (empty($_FILES['logo']['tmp_name']) || !is_uploaded_file($_FILES['logo']['tmp_name'])) return '';
        $ext = strtolower(pathinfo((string)$_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png','jpg','jpeg','gif','webp'], true)) throw new \RuntimeException('Logo 仅支持 png/jpg/jpeg/gif/webp，暂不支持 SVG');
        $dir = dirname(__DIR__, 2) . '/uploads/app-logos'; @mkdir($dir, 0755, true);
        $name = 'logo_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $dir . '/' . $name)) throw new \RuntimeException('Logo 上传失败');
        return '/uploads/app-logos/' . $name;
    }

    private function uploadGalleryImages(): array
    {
        if (empty($_FILES['gallery']['name']) || !is_array($_FILES['gallery']['name'])) return [];
        $paths = [];
        $dir = dirname(__DIR__, 2) . '/uploads/app-gallery'; @mkdir($dir, 0755, true);
        $count = count($_FILES['gallery']['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($_FILES['gallery']['tmp_name'][$i]) || !is_uploaded_file($_FILES['gallery']['tmp_name'][$i])) continue;
            $ext = strtolower(pathinfo((string)$_FILES['gallery']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, ['png','jpg','jpeg','gif','webp'], true)) throw new \RuntimeException('展示图片仅支持 png/jpg/jpeg/gif/webp');
            if ((int)($_FILES['gallery']['size'][$i] ?? 0) > 5 * 1024 * 1024) throw new \RuntimeException('单张展示图片不能超过 5MB');
            $name = 'gallery_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['gallery']['tmp_name'][$i], $dir . '/' . $name)) throw new \RuntimeException('展示图片上传失败');
            $paths[] = '/uploads/app-gallery/' . $name;
        }
        return $paths;
    }

    private function submitVersion(MarketModel $model, int $userId): void
    {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $item = $model->find($itemId);
        if (!$item || (int)($item['developer_user_id'] ?? 0) !== $userId) throw new \RuntimeException('应用不存在或无权限');
        if (empty($_FILES['package']['tmp_name']) || !is_uploaded_file($_FILES['package']['tmp_name'])) throw new \RuntimeException('请上传应用市场包 ZIP');
        $version = trim((string)($_POST['version'] ?? ''));
        if ($version === '' || !preg_match('/^[0-9A-Za-z][0-9A-Za-z._\-]{0,49}$/', $version)) throw new \RuntimeException('请填写有效版本号');
        $tmp = $_FILES['package']['tmp_name'];
        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) throw new \RuntimeException('ZIP 打开失败');
        $manifestName = $zip->locateName('market.json') !== false ? 'market.json' : ($zip->locateName('manifest.json') !== false ? 'manifest.json' : '');
        if ($manifestName === '') { $zip->close(); throw new \RuntimeException('市场包缺少 market.json'); }
        $manifest = json_decode((string)$zip->getFromName($manifestName), true);
        if (!is_array($manifest)) { $zip->close(); throw new \RuntimeException('market.json 格式错误'); }
        if (($manifest['type'] ?? '') !== $item['type'] || ($manifest['slug'] ?? '') !== $item['slug']) { $zip->close(); throw new \RuntimeException('包内 type/slug 与应用不一致'); }
        $manifest['version'] = $version;
        $zip->addFromString($manifestName, json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        if ($manifestName !== 'market.json' && $zip->locateName('market.json') === false) {
            $zip->addFromString('market.json', json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        }
        $appManifestName = ($item['type'] === 'theme') ? 'theme.json' : 'plugin.json';
        if ($zip->locateName($appManifestName) !== false) {
            $appManifest = json_decode((string)$zip->getFromName($appManifestName), true);
            if (is_array($appManifest)) {
                $appManifest['version'] = $version;
                $zip->addFromString($appManifestName, json_encode($appManifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            }
        }
        $zip->close();
        $dir = dirname(__DIR__, 2) . '/storage/market'; @mkdir($dir, 0755, true);
        $filename = $item['type'] . '_' . $item['slug'] . '_' . preg_replace('/[^a-zA-Z0-9_.\-]/', '', $version) . '_' . date('YmdHis') . '.zip';
        $path = $dir . '/' . $filename;
        if (!move_uploaded_file($tmp, $path)) throw new \RuntimeException('保存失败');
        $model->createVersion($itemId, ['version'=>$version, 'filename'=>$filename, 'hash'=>hash_file('sha256', $path), 'manifest_json'=>json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'changelog'=>(string)($_POST['changelog'] ?? '')]);
    }
}
