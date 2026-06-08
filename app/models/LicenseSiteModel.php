<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class LicenseSiteModel
{
    public function findByKeyAndDomain(int $licenseKeyId, string $domain): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM license_sites WHERE license_key_id = :license_key_id AND domain = :domain LIMIT 1");
        $stmt->execute([':license_key_id' => $licenseKeyId, ':domain' => $domain]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function bindDomain(int $licenseKeyId, string $domain): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = Database::connection()->prepare(
            "INSERT INTO license_sites (license_key_id, domain, first_seen_at, last_seen_at, status)
             VALUES (:license_key_id, :domain, :first_seen_at, :last_seen_at, 1)
             ON DUPLICATE KEY UPDATE last_seen_at = VALUES(last_seen_at)"
        );
        $stmt->execute([
            ':license_key_id' => $licenseKeyId,
            ':domain' => $domain,
            ':first_seen_at' => $now,
            ':last_seen_at' => $now,
        ]);
    }

    public function touch(int $licenseKeyId, string $domain): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE license_sites SET last_seen_at = NOW() WHERE license_key_id = :license_key_id AND domain = :domain"
        );
        $stmt->execute([':license_key_id' => $licenseKeyId, ':domain' => $domain]);
    }
}
