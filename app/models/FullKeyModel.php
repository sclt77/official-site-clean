<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class FullKeyModel
{
    public function create(int $packageId, string $key): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO full_keys (package_id, full_key, used) VALUES (:package_id, :full_key, 0)"
        );
        $stmt->execute([':package_id' => $packageId, ':full_key' => $key]);
    }

    public function markUsed(int $packageId, string $key, string $siteId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE full_keys
             SET used=1, used_site=:site_id, used_at=NOW()
             WHERE package_id=:package_id AND full_key=:full_key AND used=0"
        );
        $stmt->execute([
            ':site_id' => $siteId,
            ':package_id' => $packageId,
            ':full_key' => $key,
        ]);
    }

    public function allWithPackages(?string $product = null): array
    {
        $params = [];
        $where = '';
        if ($product !== null && $product !== '') {
            $product = strtolower(trim($product));
            if (!in_array($product, ['claybbs', 'cutot'], true)) {
                $product = 'claybbs';
            }
            $where = " WHERE COALESCE(p.product, 'claybbs') = :product";
            $params[':product'] = $product;
        }
        $sql = "SELECT fk.*, p.version, p.type, p.product
                FROM full_keys fk
                LEFT JOIN packages p ON p.id = fk.package_id" . $where . "
                ORDER BY fk.id DESC LIMIT 300";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
