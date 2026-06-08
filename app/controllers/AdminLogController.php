<?php

namespace App\Controllers;

use App\Core\Database;
use App\Middleware\AdminAuth;
use App\Models\FullKeyModel;

class AdminLogController
{
    public function index(): void
    {
        AdminAuth::check();
        $db = Database::connection();
        $siteId = trim((string) ($_GET['site_id'] ?? ''));
        $product = $this->normalizeProduct((string)($_GET['product'] ?? 'claybbs'));
        $productWhere = "COALESCE(p.product, s.product, 'claybbs') = :product";
        $siteFilter = ' WHERE ' . $productWhere . ($siteId !== '' ? ' AND pl.site_id = :site_id' : '');
        $downloadFilter = ' WHERE ' . $productWhere . ($siteId !== '' ? ' AND dl.site_id = :site_id' : '');
        $baseParams = [':product' => $product];
        if ($siteId !== '') { $baseParams[':site_id'] = $siteId; }

        $summaryStmt = $db->prepare("SELECT pl.status, COUNT(*) AS cnt FROM publish_logs pl LEFT JOIN packages p ON p.id=pl.package_id LEFT JOIN sites s ON s.site_id=pl.site_id WHERE COALESCE(p.product, s.product, 'claybbs')=:product GROUP BY pl.status");
        $summaryStmt->execute([':product' => $product]);
        $summary = $summaryStmt->fetchAll() ?: [];

        $publishSql = "SELECT pl.*, p.version, p.type, p.product AS package_product, s.product AS site_product, s.domain AS site_domain, s.email AS site_email
             FROM publish_logs pl
             LEFT JOIN packages p ON p.id = pl.package_id
             LEFT JOIN sites s ON s.site_id = pl.site_id" . $siteFilter . "
             ORDER BY pl.id DESC LIMIT 300";
        $publishStmt = $db->prepare($publishSql);
        $publishStmt->execute($baseParams);
        $publish = $publishStmt->fetchAll();

        $downloadSql = "SELECT dl.*, p.version, p.type, p.product AS package_product, s.product AS site_product, s.domain AS site_domain, s.email AS site_email, u.email AS user_email
             FROM download_logs dl
             LEFT JOIN packages p ON p.id = dl.package_id
             LEFT JOIN sites s ON s.site_id = dl.site_id
             LEFT JOIN users u ON u.id = dl.user_id" . $downloadFilter . "
             ORDER BY dl.id DESC LIMIT 300";
        $downloadStmt = $db->prepare($downloadSql);
        $downloadStmt->execute($baseParams);
        $downloads = $downloadStmt->fetchAll();

        $licenseFilter = " WHERE COALESCE(s.product, 'claybbs') = :product" . ($siteId !== '' ? ' AND s.site_id = :site_id' : '');
        $licenseSql = "SELECT ll.*, s.product AS site_product, s.site_id, s.license_key, s.domain AS site_domain, s.email AS site_email, u.email AS user_email
             FROM license_logs ll
             LEFT JOIN sites s ON s.id = ll.site_id
             LEFT JOIN users u ON u.id = s.user_id" . $licenseFilter . "
             ORDER BY ll.id DESC LIMIT 300";
        $licenseStmt = $db->prepare($licenseSql);
        $licenseStmt->execute($baseParams);
        $licenseLogs = $licenseStmt->fetchAll();

        $keys = (new FullKeyModel())->allWithPackages($product);
        if ($siteId !== '') {
            $keys = array_values(array_filter($keys, static function ($row) use ($siteId) {
                return (string) ($row['used_site'] ?? '') === $siteId;
            }));
        }
        require dirname(__DIR__) . '/views/admin/logs.php';
    }

    private function normalizeProduct(string $product): string
    {
        $product = strtolower(trim($product));
        return in_array($product, ['claybbs', 'cutot'], true) ? $product : 'claybbs';
    }
}

