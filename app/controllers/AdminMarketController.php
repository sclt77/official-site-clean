<?php

namespace App\Controllers;

use App\Middleware\AdminAuth;
use App\Models\MarketModel;

class AdminMarketController
{
    public function index(): void
    {
        AdminAuth::check();
        $model = new MarketModel();
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = (string)($_POST['_action'] ?? '');
            try {
                if ($action === 'save_category') {
                    $model->saveCategory([
                        'id' => (int)($_POST['id'] ?? 0),
                        'type' => (string)($_POST['category_type'] ?? ''),
                        'name' => (string)($_POST['name'] ?? ''),
                        'slug' => (string)($_POST['slug'] ?? ''),
                        'sort_order' => (int)($_POST['sort_order'] ?? 0),
                        'status' => (string)($_POST['status'] ?? 'active'),
                    ]);
                    redirect_or_ajax('/admin.php?path=market&tab=categories');
                }
                if ($action === 'toggle_category') {
                    $model->toggleCategory((int)$_POST['id'], (string)$_POST['status']);
                    redirect_or_ajax('/admin.php?path=market&tab=categories');
                }
                if ($action === 'delete_category') {
                    $model->deleteCategory((int)$_POST['id']);
                    redirect_or_ajax('/admin.php?path=market&tab=categories');
                }
                if ($action === 'grant_license') {
                    $userId = (int)($_POST['user_id'] ?? 0);
                    $itemId = (int)($_POST['item_id'] ?? 0);
                    if ($userId <= 0 || $itemId <= 0) throw new \RuntimeException('请选择用户和应用');
                    $model->grantLicenseToUser($userId, $itemId, trim((string)($_POST['bound_domain'] ?? '')));
                    redirect_or_ajax('/admin.php?path=market&tab=licenses');
                }
                if ($action === 'unbind_license') {
                    $licenseId = (int)($_POST['license_id'] ?? 0);
                    if ($licenseId <= 0) throw new \RuntimeException('授权不存在');
                    $model->unbindLicenseById($licenseId);
                    redirect_or_ajax('/admin.php?path=market&tab=licenses');
                }
                if ($action === 'review_version') {
                    $status = (string)($_POST['status'] ?? '');
                    if (!in_array($status, ['published','rejected'], true)) { throw new \RuntimeException('审核状态无效，请重新提交'); }
                    $model->reviewVersion((int)$_POST['version_id'], $status, (string)($_POST['review_note'] ?? ''));
                    redirect_or_ajax('/admin.php?path=market&tab=review');
                }
                if ($action === 'review_appeal') {
                    $status = (string)($_POST['status'] ?? '');
                    if (!in_array($status, ['approved','rejected'], true)) { throw new \RuntimeException('申诉处理状态无效'); }
                    $model->reviewAppeal((int)$_POST['appeal_id'], $status, (string)($_POST['review_note'] ?? ''));
                    redirect_or_ajax('/admin.php?path=market&tab=appeals');
                }
                if ($action === 'toggle') {
                    $model->toggle((int)$_POST['id'], (string)$_POST['status']);
                    redirect_or_ajax('/admin.php?path=market&tab=apps');
                }
                if ($action === 'delete') {
                    $item = $model->find((int)$_POST['id']);
                    if ($item && !empty($item['filename'])) @unlink(dirname(__DIR__, 2) . '/storage/market/' . basename($item['filename']));
                    $model->delete((int)$_POST['id']);
                    redirect_or_ajax('/admin.php?path=market&tab=apps');
                }
                if ($action === 'review_developer_application') {
                    $model->reviewDeveloperApplication((int)($_POST['application_id'] ?? 0), (string)($_POST['status'] ?? ''), (string)($_POST['review_note'] ?? ''));
                    redirect_or_ajax('/admin.php?path=market&tab=developer_apps');
                }
                if ($action === 'mark_developer_order_paid') {
                    $orderNo = trim((string)($_POST['order_no'] ?? ''));
                    if ($orderNo === '') throw new \RuntimeException('订单号不能为空');
                    $model->markDeveloperOrderPaid($orderNo, trim((string)($_POST['trade_no'] ?? 'ADMIN-MANUAL')));
                    redirect_or_ajax('/admin.php?path=market&tab=developer_orders');
                }
                if ($action === 'close_developer_order') {
                    $orderNo = trim((string)($_POST['order_no'] ?? ''));
                    if ($orderNo === '') throw new \RuntimeException('订单号不能为空');
                    $model->closeDeveloperOrder($orderNo);
                    redirect_or_ajax('/admin.php?path=market&tab=developer_orders');
                }
                if ($action === 'mark_order_paid') {
                    $orderNo = trim((string)($_POST['order_no'] ?? ''));
                    if ($orderNo === '') throw new \RuntimeException('订单号不能为空');
                    $model->markOrderPaid($orderNo, trim((string)($_POST['trade_no'] ?? 'ADMIN-MANUAL')));
                    redirect_or_ajax('/admin.php?path=market&tab=orders');
                }
                if ($action === 'review_withdrawal') {
                    $model->reviewWithdrawal((int)($_POST['withdrawal_id'] ?? 0), (string)($_POST['status'] ?? ''), (string)($_POST['review_note'] ?? ''));
                    redirect_or_ajax('/admin.php?path=market&tab=withdrawals');
                }
                if ($action === 'close_order') {
                    $orderNo = trim((string)($_POST['order_no'] ?? ''));
                    if ($orderNo === '') throw new \RuntimeException('订单号不能为空');
                    $model->closeOrder($orderNo);
                    redirect_or_ajax('/admin.php?path=market&tab=orders');
                }
            } catch (\Throwable $e) { $error = '操作失败：' . $e->getMessage(); }
        }
        $q = trim((string)($_GET['q'] ?? ''));
        $type = trim((string)($_GET['type'] ?? ''));
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $status = trim((string)($_GET['status'] ?? ''));
        $pluginCategories = $model->categories('plugin');
        $themeCategories = $model->categories('theme');
        $categories = array_merge($pluginCategories, $themeCategories);
        $pendingVersions = $model->pendingVersions();
        $appeals = $model->appeals(false);
        $orderFilters = [
            'q' => trim((string)($_GET['order_q'] ?? '')),
            'status' => trim((string)($_GET['order_status'] ?? '')),
            'type' => trim((string)($_GET['order_type'] ?? '')),
        ];
        $orders = $model->orders($orderFilters);
        $orderStats = $model->orderStats();
        $developerOrderFilters = [
            'q' => trim((string)($_GET['developer_order_q'] ?? '')),
            'status' => trim((string)($_GET['developer_order_status'] ?? '')),
        ];
        $developerApplications = $model->developerApplications();
        $developerOrders = $model->developerJoinOrders($developerOrderFilters);
        $developerOrderStats = $model->developerOrderStats();
        $withdrawals = $model->withdrawals();
        $pendingAppeals = array_values(array_filter($appeals, fn($a) => ($a['status'] ?? '') === 'pending'));
        $items = $model->all(null, false, $categoryId > 0 ? $categoryId : null);
        $allMarketItems = $model->all(null, false, null);
        $licenseFilters = ['q' => trim((string)($_GET['license_q'] ?? ''))];
        $licenses = $model->licenses($licenseFilters);
        $grantUsers = \App\Core\Database::connection()->query("SELECT id,email,name FROM users ORDER BY id DESC LIMIT 500")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        if ($q !== '') { $items = array_values(array_filter($items, fn($it) => str_contains(strtolower((string)$it['name']), strtolower($q)) || str_contains(strtolower((string)$it['slug']), strtolower($q)))); }
        if (in_array($type, ['plugin','theme'], true)) { $items = array_values(array_filter($items, fn($it) => ($it['type'] ?? '') === $type)); }
        if ($status !== '') { $items = array_values(array_filter($items, fn($it) => ($it['status'] ?? '') === $status)); }
        require dirname(__DIR__) . '/views/admin/market.php';
    }
}
