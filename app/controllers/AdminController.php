<?php

namespace App\Controllers;

use App\Core\Database;
use App\Middleware\AdminAuth;
use App\Models\SiteLimitRequestModel;

class AdminController
{
    public function index(): void
    {
        AdminAuth::check();
        $db = Database::connection();
        $stats = [
            'users' => (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'sites' => (int) $db->query("SELECT COUNT(*) FROM sites")->fetchColumn(),
            'claybbs_sites' => (int) $db->query("SELECT COUNT(*) FROM sites WHERE product='claybbs'")->fetchColumn(),
            'cutot_sites' => (int) $db->query("SELECT COUNT(*) FROM sites WHERE product='cutot'")->fetchColumn(),
            'packages' => (int) $db->query("SELECT COUNT(*) FROM packages")->fetchColumn(),
            'claybbs_packages' => (int) $db->query("SELECT COUNT(*) FROM packages WHERE product='claybbs'")->fetchColumn(),
            'cutot_packages' => (int) $db->query("SELECT COUNT(*) FROM packages WHERE product='cutot'")->fetchColumn(),
            'downloads' => (int) $db->query("SELECT COUNT(*) FROM download_logs")->fetchColumn(),
            'logs' => (int) $db->query("SELECT COUNT(*) FROM publish_logs")->fetchColumn(),
        ];
        $pendingLimitRequestCountClay = (new SiteLimitRequestModel())->countByStatus('pending', 'claybbs');
        $pendingLimitRequestCountCutot = (new SiteLimitRequestModel())->countByStatus('pending', 'cutot');
        $pendingLimitRequestCount = $pendingLimitRequestCountClay + $pendingLimitRequestCountCutot;
        require dirname(__DIR__) . '/views/admin/index.php';
    }
}
