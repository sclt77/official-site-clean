<?php

namespace App\Controllers;

use App\Middleware\AdminAuth;
use App\Models\MarketModel;

class AdminOrderController
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
                if ($action === 'mark_order_paid') {
                    $orderNo = trim((string)($_POST['order_no'] ?? ''));
                    if ($orderNo === '') throw new \RuntimeException('订单号不能为空');
                    $model->markOrderPaid($orderNo, trim((string)($_POST['trade_no'] ?? 'ADMIN-MANUAL')));
                    redirect_or_ajax('/admin.php?path=orders&tab=market');
                }
                if ($action === 'close_order') {
                    $orderNo = trim((string)($_POST['order_no'] ?? ''));
                    if ($orderNo === '') throw new \RuntimeException('订单号不能为空');
                    $model->closeOrder($orderNo);
                    redirect_or_ajax('/admin.php?path=orders&tab=market');
                }
                if ($action === 'mark_developer_order_paid') {
                    $orderNo = trim((string)($_POST['order_no'] ?? ''));
                    if ($orderNo === '') throw new \RuntimeException('订单号不能为空');
                    $model->markDeveloperOrderPaid($orderNo, trim((string)($_POST['trade_no'] ?? 'ADMIN-MANUAL')));
                    redirect_or_ajax('/admin.php?path=orders&tab=developer');
                }
                if ($action === 'close_developer_order') {
                    $orderNo = trim((string)($_POST['order_no'] ?? ''));
                    if ($orderNo === '') throw new \RuntimeException('订单号不能为空');
                    $model->closeDeveloperOrder($orderNo);
                    redirect_or_ajax('/admin.php?path=orders&tab=developer');
                }
                if ($action === 'review_withdrawal') {
                    $model->reviewWithdrawal((int)($_POST['withdrawal_id'] ?? 0), (string)($_POST['status'] ?? ''), (string)($_POST['review_note'] ?? ''));
                    redirect_or_ajax('/admin.php?path=orders&tab=withdrawals');
                }
            } catch (\Throwable $e) {
                $error = '操作失败：' . $e->getMessage();
            }
        }

        $orderFilters = [
            'q' => trim((string)($_GET['order_q'] ?? '')),
            'status' => trim((string)($_GET['order_status'] ?? '')),
            'type' => trim((string)($_GET['order_type'] ?? '')),
        ];
        $developerOrderFilters = [
            'q' => trim((string)($_GET['developer_order_q'] ?? '')),
            'status' => trim((string)($_GET['developer_order_status'] ?? '')),
        ];

        $orders = $model->orders($orderFilters);
        $orderStats = $model->orderStats();
        $developerOrders = $model->developerJoinOrders($developerOrderFilters);
        $developerOrderStats = $model->developerOrderStats();
        $withdrawals = $model->withdrawals();

        require dirname(__DIR__) . '/views/admin/orders.php';
    }
}
