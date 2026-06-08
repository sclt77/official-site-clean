<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SiteModel
{
    public function create(int $userId, string $domain, string $email, string $product = 'claybbs'): array
    {
        $product = $this->normalizeProduct($product);
        $exists = Database::connection()->prepare(
            "SELECT id FROM sites WHERE user_id=:user_id AND domain=:domain AND product=:product LIMIT 1"
        );
        $exists->execute([
            ':user_id' => $userId,
            ':domain' => $domain,
            ':product' => $product,
        ]);
        if ($exists->fetch(PDO::FETCH_ASSOC)) {
            throw new \RuntimeException('该域名已绑定过，无需重复创建');
        }

        $limitColumn = $product === 'cutot' ? 'cutot_site_limit' : 'claybbs_site_limit';
        $limitStmt = Database::connection()->prepare("SELECT COALESCE($limitColumn, site_limit, 0) FROM users WHERE id=:user_id LIMIT 1");
        $limitStmt->execute([':user_id' => $userId]);
        $limit = max(0, (int) $limitStmt->fetchColumn());

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) FROM sites WHERE user_id=:user_id AND product=:product");
        $countStmt->execute([':user_id' => $userId, ':product' => $product]);
        $count = (int) $countStmt->fetchColumn();
        if ($count >= $limit) {
            throw new \RuntimeException(($product === 'cutot' ? 'CUTOT' : 'ClayBBS') . ' 当前账号最多绑定 ' . $limit . ' 个域名，请联系管理员调整绑定数量');
        }

        $siteId = 'site_' . bin2hex(random_bytes(6));
        $token = bin2hex(random_bytes(16));
        $licenseKey = ($product === 'cutot' ? 'CUTOT-' : 'LIC-') . strtoupper(bin2hex(random_bytes(6)));
        $licenseType = $product === 'claybbs' ? 'trial' : 'permanent';
        $licenseExpiresAt = $product === 'claybbs' ? date('Y-m-d H:i:s', time() + 7 * 86400) : null;
        $this->ensureLicenseColumns();
        $stmt = Database::connection()->prepare(
            "INSERT INTO sites (user_id, product, site_id, token, domain, email, status, license_key, license_status, license_type, license_expires_at, bound_at)
             VALUES (:user_id, :product, :site_id, :token, :domain, :email, 'active', :license_key, 'active', :license_type, :license_expires_at, NOW())"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':product' => $product,
            ':site_id' => $siteId,
            ':token' => $token,
            ':domain' => $domain,
            ':email' => $email,
            ':license_key' => $licenseKey,
            ':license_type' => $licenseType,
            ':license_expires_at' => $licenseExpiresAt,
        ]);
        return ['site_id' => $siteId, 'token' => $token, 'license_key' => $licenseKey];
    }


    public function findByIdForUser(int $id, int $userId): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM sites WHERE id=:id AND user_id=:user_id LIMIT 1");
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function unbindByUser(int $id, int $userId): void
    {
        $site = $this->findByIdForUser($id, $userId);
        if (!$site) {
            throw new \RuntimeException('授权不存在或无权操作');
        }
        Database::connection()->prepare("DELETE FROM sites WHERE id=:id AND user_id=:user_id LIMIT 1")
            ->execute([':id' => $id, ':user_id' => $userId]);
    }

    public function delete(int $id): void
    {
        Database::connection()->prepare("DELETE FROM sites WHERE id=:id LIMIT 1")->execute([':id' => $id]);
    }

    public function findByLicenseKey(string $licenseKey, ?string $product = null): ?array
    {
        $this->ensureLicenseColumns();
        $where = 's.license_key = :license_key';
        $params = [':license_key' => $licenseKey];
        if ($product !== null && $product !== '') {
            $where .= ' AND s.product = :product';
            $params[':product'] = $this->normalizeProduct($product);
        }
        $stmt = Database::connection()->prepare("SELECT s.*, u.name AS user_name, u.email AS user_email FROM sites s LEFT JOIN users u ON u.id=s.user_id WHERE $where LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function allByUser(int $userId, ?string $product = null): array
    {
        $where = 'user_id=:user_id';
        $params = [':user_id' => $userId];
        if ($product !== null && $product !== '') {
            $where .= ' AND product=:product';
            $params[':product'] = $this->normalizeProduct($product);
        }
        $stmt = Database::connection()->prepare("SELECT * FROM sites WHERE $where ORDER BY id DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySiteId(string $siteId): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM sites WHERE site_id=:site_id LIMIT 1");
        $stmt->execute([':site_id' => $siteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function allWithUsers(?string $product = null): array
    {
        $this->ensureLicenseColumns();
        $where = '';
        $params = [];
        if ($product !== null && $product !== '') {
            $where = 'WHERE s.product=:product';
            $params[':product'] = $this->normalizeProduct($product);
        }
        $sql = "SELECT s.*, u.email AS user_email, u.name AS user_name
                FROM sites s
                LEFT JOIN users u ON u.id = s.user_id
                $where
                ORDER BY s.id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->ensureLicenseColumns();
        $allowed = ['active', 'disabled', 'locked'];
        $status = in_array($status, $allowed, true) ? $status : 'active';
        $licenseStatus = $status === 'active' ? 'active' : ($status === 'locked' ? 'locked' : 'disabled');
        $stmt = Database::connection()->prepare("UPDATE sites SET status=:status, license_status=:license_status WHERE id=:id");
        $stmt->execute([':status' => $status, ':license_status' => $licenseStatus, ':id' => $id]);
    }

    public function convertLicense(int $id, string $type, ?string $expiresAt = null): void
    {
        $this->ensureLicenseColumns();
        $type = $type === 'trial' ? 'trial' : 'permanent';
        if ($type === 'trial') {
            $ts = $expiresAt ? strtotime($expiresAt) : false;
            if (!$ts) {
                $ts = time() + 7 * 86400;
            }
            $expiresAt = date('Y-m-d H:i:s', $ts);
            $licenseStatus = $ts < time() ? 'expired' : 'active';
        } else {
            $expiresAt = null;
            $licenseStatus = 'active';
        }
        $stmt = Database::connection()->prepare("UPDATE sites SET license_type=:license_type, license_expires_at=:expires_at, license_status=:license_status, status=IF(status='disabled' OR status='locked', status, 'active'), updated_at=NOW() WHERE id=:id");
        $stmt->execute([':license_type' => $type, ':expires_at' => $expiresAt, ':license_status' => $licenseStatus, ':id' => $id]);
    }

    public function resetToken(int $id): string
    {
        $token = bin2hex(random_bytes(16));
        $stmt = Database::connection()->prepare("UPDATE sites SET token=:token, updated_at=NOW() WHERE id=:id");
        $stmt->execute([':token' => $token, ':id' => $id]);
        return $token;
    }

    public function ensureLicenseColumns(): void
    {
        $db = Database::connection();
        try { $db->exec("ALTER TABLE sites ADD COLUMN license_type VARCHAR(20) NOT NULL DEFAULT 'permanent' AFTER license_status"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE sites ADD COLUMN license_expires_at DATETIME DEFAULT NULL AFTER license_type"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE sites ADD KEY idx_sites_license_type (license_type)"); } catch (\Throwable $e) {}
    }

    private function normalizeProduct(string $product): string
    {
        $product = strtolower(trim($product));
        return in_array($product, ['claybbs', 'cutot'], true) ? $product : 'claybbs';
    }

    public function touch(string $siteId): void
    {
        $stmt = Database::connection()->prepare("UPDATE sites SET last_seen_at=NOW() WHERE site_id=:site_id");
        $stmt->execute([':site_id' => $siteId]);
    }
}
