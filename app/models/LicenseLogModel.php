<?php

namespace App\Models;

use App\Core\Database;

class LicenseLogModel
{
    public function create(int $siteId, string $domain, string $ip, string $action, string $detail = ''): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO license_logs (site_id, domain, ip, action, detail)
             VALUES (:site_id, :domain, :ip, :action, :detail)"
        );
        $stmt->execute([
            ':site_id' => $siteId,
            ':domain' => $domain,
            ':ip' => $ip,
            ':action' => $action,
            ':detail' => $detail !== '' ? $detail : null,
        ]);
    }
}
