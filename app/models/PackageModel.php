<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class PackageModel
{
    public function latestDiff(string $currentVersion, string $branch = 'main', string $product = 'claybbs'): ?array
    {
        $product = $this->normalizeProduct($product);
        $stmt = Database::connection()->prepare(
            "SELECT * FROM packages
             WHERE type='diff' AND status='published' AND branch=:branch AND product=:product
             ORDER BY id ASC"
        );
        $stmt->execute([':branch' => $branch, ':product' => $product]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 更新必须逐级返回：例如当前 1.0.1 时优先返回 from_version=1.0.1 的 1.0.2，
        // 不能直接返回 1.0.3。这样论坛端迁移脚本可按版本顺序执行。
        $direct = null;
        foreach ($rows as $row) {
            $from = trim((string)($row['from_version'] ?? ''));
            $version = trim((string)($row['version'] ?? '0.0.0'));
            if ($from !== '' && version_compare($from, $currentVersion, '==') && version_compare($version, $currentVersion, '>')) {
                if ($direct === null || version_compare($version, (string)$direct['version'], '<')) {
                    $direct = $row;
                }
            }
        }
        if ($direct !== null) {
            return $direct;
        }

        // 兼容旧包：如果历史包没有 from_version，只返回大于当前版本的最小版本，仍保持逐级。
        $next = null;
        foreach ($rows as $row) {
            $from = trim((string)($row['from_version'] ?? ''));
            if ($from !== '') {
                continue;
            }
            $version = trim((string)($row['version'] ?? '0.0.0'));
            if (version_compare($version, $currentVersion, '<=')) {
                continue;
            }
            if ($next === null || version_compare($version, (string)$next['version'], '<')) {
                $next = $row;
            }
        }

        return $next;
    }


    public function latestPublishedFull(string $product = 'claybbs'): ?array
    {
        $product = $this->normalizeProduct($product);
        $stmt = Database::connection()->prepare("SELECT * FROM packages WHERE type='full' AND status='published' AND product=:product ORDER BY id DESC LIMIT 1");
        $stmt->execute([':product' => $product]);
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        return $row ?: null;
    }

    public function isLatestPublishedFull(int $id, string $product = 'claybbs'): bool
    {
        $latest = $this->latestPublishedFull($product);
        return $latest && (int)($latest['id'] ?? 0) === $id;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM packages WHERE id=:id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO packages (
                product, type, version, from_version, branch, filename, rollback_filename, full_filename,
                hash, signature, package_hash, package_signature, full_hash, full_signature, rollback_hash, rollback_signature,
                manifest_json, file_size, full_file_size, rollback_file_size, notes, has_code, has_db, status,
                update_level, force_update, min_version, max_version
             ) VALUES (
                :product, :type, :version, :from_version, :branch, :filename, :rollback_filename, :full_filename,
                :hash, :signature, :package_hash, :package_signature, :full_hash, :full_signature, :rollback_hash, :rollback_signature,
                :manifest_json, :file_size, :full_file_size, :rollback_file_size, :notes, :has_code, :has_db, :status,
                :update_level, :force_update, :min_version, :max_version
             )"
        );
        $stmt->execute([
            ':product' => $this->normalizeProduct((string)($data['product'] ?? 'claybbs')),
            ':type' => $data['type'],
            ':version' => $data['version'],
            ':from_version' => $data['from_version'] ?? null,
            ':branch' => $data['branch'] ?? 'main',
            ':filename' => $data['filename'],
            ':rollback_filename' => $data['rollback_filename'] ?? null,
            ':full_filename' => $data['full_filename'] ?? null,
            ':hash' => $data['hash'],
            ':signature' => $data['signature'] ?? '',
            ':package_hash' => $data['package_hash'] ?? $data['hash'],
            ':package_signature' => $data['package_signature'] ?? ($data['signature'] ?? ''),
            ':full_hash' => $data['full_hash'] ?? null,
            ':full_signature' => $data['full_signature'] ?? null,
            ':rollback_hash' => $data['rollback_hash'] ?? null,
            ':rollback_signature' => $data['rollback_signature'] ?? null,
            ':manifest_json' => $data['manifest_json'] ?? null,
            ':file_size' => $data['file_size'] ?? null,
            ':full_file_size' => $data['full_file_size'] ?? null,
            ':rollback_file_size' => $data['rollback_file_size'] ?? null,
            ':notes' => $data['notes'] ?? '',
            ':has_code' => !empty($data['has_code']) ? 1 : 0,
            ':has_db' => !empty($data['has_db']) ? 1 : 0,
            ':status' => $data['status'] ?? 'published',
            ':update_level' => $data['update_level'] ?? 'normal',
            ':force_update' => !empty($data['force_update']) ? 1 : 0,
            ':min_version' => $data['min_version'] ?? null,
            ':max_version' => $data['max_version'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare("UPDATE packages SET status=:status WHERE id=:id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare("DELETE FROM packages WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    public function updateSignatureAndHash(int $id, string $signature, string $hash): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE packages SET signature=:signature, hash=:hash, updated_at=NOW() WHERE id=:id"
        );
        $stmt->execute([
            ':signature' => $signature,
            ':hash' => $hash,
            ':id' => $id,
        ]);
    }

    public function all(?string $type = null, ?string $product = null): array
    {
        $where = [];
        $params = [];
        if ($type !== null && $type !== '') { $where[] = 'type=:type'; $params[':type'] = $type; }
        if ($product !== null && $product !== '') { $where[] = 'product=:product'; $params[':product'] = $this->normalizeProduct($product); }
        $sql = 'SELECT * FROM packages' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 300';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allPublished(?string $type = null, ?string $product = null): array
    {
        $where = ["status='published'"];
        $params = [];
        if ($type !== null && $type !== '') { $where[] = 'type=:type'; $params[':type'] = $type; }
        if ($product !== null && $product !== '') { $where[] = 'product=:product'; $params[':product'] = $this->normalizeProduct($product); }
        $stmt = Database::connection()->prepare('SELECT * FROM packages WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 300');
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function normalizeProduct(string $product): string
    {
        $product = strtolower(trim($product));
        return in_array($product, ['claybbs', 'cutot'], true) ? $product : 'claybbs';
    }

    public function markFullKeyUsed(int $id, string $siteId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE packages
             SET full_key_used=1, full_key_used_site=:site_id, full_key_used_at=NOW()
             WHERE id=:id"
        );
        $stmt->execute([':site_id' => $siteId, ':id' => $id]);
    }
}
