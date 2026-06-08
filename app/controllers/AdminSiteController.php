<?php

namespace App\Controllers;

use App\Core\Database;
use App\Middleware\AdminAuth;
use App\Models\SiteModel;
use App\Models\SiteLimitRequestModel;

class AdminSiteController
{
    public function index(): void
    {
        AdminAuth::check();
        $product = $this->normalizeProduct((string)($_GET['product'] ?? $_POST['product'] ?? 'claybbs'));
        $productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
        $rows = (new SiteModel())->allWithUsers($product);
        $users = [];
        $sitesByUser = [];
        foreach ($rows as $row) {
            $uid = (int) ($row['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            if (!isset($users[$uid])) {
                $users[$uid] = [
                    'user_id' => $uid,
                    'user_email' => (string) ($row['user_email'] ?? ''),
                    'user_name' => (string) ($row['user_name'] ?? ''),
                ];
            }
            $sitesByUser[$uid][] = $row;
        }
        $requestModel = new SiteLimitRequestModel();
        $requestStatus = (string)($_GET['request_status'] ?? '');
        $limitRequests = $requestModel->all($requestStatus, $product);
        $pendingLimitRequestCount = $requestModel->countByStatus('pending', $product);
        require dirname(__DIR__) . '/views/admin/sites.php';
    }


    public function reviewLimitRequest(): void
    {
        AdminAuth::check();
        csrf_verify();
        $id = (int)($_POST['id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');
        if ($id > 0 && in_array($action, ['approve','reject'], true)) {
            (new SiteLimitRequestModel())->review($id, $action, (int)($_SESSION['auth_user']['id'] ?? 0), trim((string)($_POST['review_note'] ?? '')));
        }
        redirect_or_ajax('/admin.php?path=sites&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
    }

    public function update(): void
    {
        AdminAuth::check();
        csrf_verify();

        $id = (int) ($_POST['id'] ?? 0);
        $action = trim((string) ($_POST['_action'] ?? ''));
        $siteModel = new SiteModel();

        if ($id <= 0) {
            redirect_or_ajax('/admin.php?path=sites&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
        }

        if ($action === 'toggle_status') {
            $status = trim((string) ($_POST['status'] ?? 'active'));
            $siteModel->updateStatus($id, $status);
        }

        if ($action === 'reset_token') {
            $siteModel->resetToken($id);
        }

        if ($action === 'convert_license') {
            $type = trim((string)($_POST['license_type'] ?? 'permanent'));
            $expiresAt = trim((string)($_POST['license_expires_at'] ?? ''));
            $siteModel->convertLicense($id, $type, $expiresAt !== '' ? $expiresAt : null);
        }

        if ($action === 'delete') {
            $siteModel->delete($id);
        }

        redirect_or_ajax('/admin.php?path=sites&product=' . urlencode((string)($_POST['product'] ?? $_GET['product'] ?? 'claybbs')));
    }
    private function normalizeProduct(string $product): string
    {
        $product = strtolower(trim($product));
        return in_array($product, ['claybbs', 'cutot'], true) ? $product : 'claybbs';
    }
}

