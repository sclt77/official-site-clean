<?php

namespace App\Controllers;

use App\Core\Database;
use App\Middleware\AdminAuth;

class AdminUserController
{
    public function index(): void
    {
        AdminAuth::check();
        $db = Database::connection();
        try { $db->exec("ALTER TABLE users ADD COLUMN developer_level VARCHAR(30) NOT NULL DEFAULT 'none'"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN site_limit INT UNSIGNED NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN claybbs_site_limit INT UNSIGNED NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN cutot_site_limit INT UNSIGNED NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}
        try { $db->exec("UPDATE users SET site_limit=0 WHERE site_limit IS NULL"); } catch (\Throwable $e) {}
        try { $db->exec("UPDATE users SET claybbs_site_limit=site_limit WHERE claybbs_site_limit=0 AND site_limit>0"); } catch (\Throwable $e) {}
        try { $db->exec("UPDATE users SET cutot_site_limit=0 WHERE cutot_site_limit IS NULL"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 1"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_verify_token VARCHAR(64) DEFAULT NULL"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_verify_expires_at DATETIME DEFAULT NULL"); } catch (\Throwable $e) {}

        $q = trim((string)($_GET['q'] ?? ''));
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE u.email LIKE :q OR u.name LIKE :q OR CAST(u.id AS CHAR) = :id_exact";
            $params[':q'] = '%' . $q . '%';
            $params[':id_exact'] = ctype_digit($q) ? $q : '-1';
        }

        $stmt = $db->prepare("SELECT u.id,u.email,u.name,u.role,u.status,u.developer_level,u.site_limit,u.email_verified,u.created_at,SUM(CASE WHEN s.product='claybbs' THEN 1 ELSE 0 END) AS claybbs_site_count, SUM(CASE WHEN s.product='cutot' THEN 1 ELSE 0 END) AS cutot_site_count, COUNT(s.id) AS site_count FROM users u LEFT JOIN sites s ON s.user_id=u.id {$where} GROUP BY u.id ORDER BY u.id DESC LIMIT 300");
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        require dirname(__DIR__) . '/views/admin/users.php';
    }

    public function toggle(): void
    {
        AdminAuth::check();
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'active'));
        $role = trim((string) ($_POST['role'] ?? ''));
        $developerLevel = trim((string) ($_POST['developer_level'] ?? 'none'));
        if (!in_array($developerLevel, ['none','public','normal','professional','official'], true)) { $developerLevel = 'none'; }
        if ($developerLevel === 'none') { $role = 'user'; }
        if ($role === 'user') { $developerLevel = 'none'; }
        if ($role === 'developer' && $developerLevel === 'none') { $developerLevel = 'public'; }
        $claybbsSiteLimit = max(0, min(999, (int) ($_POST['claybbs_site_limit'] ?? ($_POST['site_limit'] ?? 0))));
        $cutotSiteLimit = max(0, min(999, (int) ($_POST['cutot_site_limit'] ?? 0)));
        $siteLimit = $claybbsSiteLimit;
        $emailVerified = !empty($_POST['email_verified']) ? 1 : 0;

        $db = Database::connection();
        if ($role !== '') {
            $db->prepare("UPDATE users SET role=:role, developer_level=:developer_level, site_limit=:site_limit, claybbs_site_limit=:claybbs_site_limit, cutot_site_limit=:cutot_site_limit, email_verified=:email_verified, email_verify_token=IF(:email_verified=1,NULL,email_verify_token), email_verify_expires_at=IF(:email_verified=1,NULL,email_verify_expires_at) WHERE id=:id")
                ->execute([':role' => $role, ':developer_level' => $developerLevel, ':site_limit' => $siteLimit, ':claybbs_site_limit' => $claybbsSiteLimit, ':cutot_site_limit' => $cutotSiteLimit, ':email_verified' => $emailVerified, ':id' => $id]);
        } else {
            $db->prepare("UPDATE users SET site_limit=:site_limit, claybbs_site_limit=:claybbs_site_limit, cutot_site_limit=:cutot_site_limit, email_verified=:email_verified, email_verify_token=IF(:email_verified=1,NULL,email_verify_token), email_verify_expires_at=IF(:email_verified=1,NULL,email_verify_expires_at) WHERE id=:id")
                ->execute([':site_limit' => $siteLimit, ':claybbs_site_limit' => $claybbsSiteLimit, ':cutot_site_limit' => $cutotSiteLimit, ':email_verified' => $emailVerified, ':id' => $id]);
        }
        $db->prepare("UPDATE users SET status=:status WHERE id=:id")->execute([':status' => $status, ':id' => $id]);
        redirect_or_ajax('/admin.php?path=users');
    }
}
