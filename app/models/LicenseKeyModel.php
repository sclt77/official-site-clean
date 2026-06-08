<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class LicenseKeyModel
{
    public function findByKey(string $licenseKey): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM license_keys WHERE license_key = :license_key LIMIT 1");
        $stmt->execute([':license_key' => $licenseKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function countActiveSites(int $licenseKeyId): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM license_sites WHERE license_key_id = :license_key_id AND status = 1");
        $stmt->execute([':license_key_id' => $licenseKeyId]);
        return (int) $stmt->fetchColumn();
    }

    public function create(string $licenseKey, string $plan = 'pro', int $maxSites = 1, ?string $expiresAt = null, string $remark = ''): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO license_keys (license_key, plan, max_sites, expires_at, status, remark)
             VALUES (:license_key, :plan, :max_sites, :expires_at, 1, :remark)"
        );
        $stmt->execute([
            ':license_key' => $licenseKey,
            ':plan' => $plan,
            ':max_sites' => $maxSites,
            ':expires_at' => $expiresAt,
            ':remark' => $remark !== '' ? $remark : null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }
}
