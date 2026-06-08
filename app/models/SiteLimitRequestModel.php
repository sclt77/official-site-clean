<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SiteLimitRequestModel
{
    public function ensureTable(): void
    {
        Database::connection()->exec("CREATE TABLE IF NOT EXISTS site_limit_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            product VARCHAR(20) NOT NULL DEFAULT 'claybbs',
            current_limit INT UNSIGNED NOT NULL DEFAULT 0,
            current_used INT UNSIGNED NOT NULL DEFAULT 0,
            requested_count INT UNSIGNED NOT NULL DEFAULT 1,
            reason TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            review_note TEXT DEFAULT NULL,
            reviewed_by BIGINT UNSIGNED DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_site_limit_requests_user_status (user_id,status),
            KEY idx_site_limit_requests_user_product_status (user_id,product,status),
            KEY idx_site_limit_requests_status (status,id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        Database::connection()->exec("CREATE TABLE IF NOT EXISTS site_limit_orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_no VARCHAR(64) NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            product VARCHAR(20) NOT NULL DEFAULT 'claybbs',
            requested_count INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            pay_type VARCHAR(30) NOT NULL DEFAULT 'alipay',
            trade_no VARCHAR(120) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            paid_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_site_limit_orders_order_no (order_no),
            KEY idx_site_limit_orders_user_status (user_id,status),
            KEY idx_site_limit_orders_user_product_status (user_id,product,status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try { Database::connection()->exec("ALTER TABLE site_limit_requests ADD COLUMN product VARCHAR(20) NOT NULL DEFAULT 'claybbs' AFTER user_id"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("ALTER TABLE site_limit_orders ADD COLUMN product VARCHAR(20) NOT NULL DEFAULT 'claybbs' AFTER user_id"); } catch (\Throwable $e) {}
    }

    public function pendingForUser(int $userId, string $product = 'claybbs'): ?array
    {
        $this->ensureTable();
        $product = $this->normalizeProduct($product);
        $stmt = Database::connection()->prepare("SELECT * FROM site_limit_requests WHERE user_id=:uid AND product=:product AND status='pending' ORDER BY id DESC LIMIT 1");
        $stmt->execute([':uid' => $userId, ':product' => $product]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function latestForUser(int $userId, string $product = 'claybbs'): ?array
    {
        $this->ensureTable();
        $product = $this->normalizeProduct($product);
        $stmt = Database::connection()->prepare("SELECT * FROM site_limit_requests WHERE user_id=:uid AND product=:product ORDER BY id DESC LIMIT 1");
        $stmt->execute([':uid' => $userId, ':product' => $product]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function approvedForUser(int $userId, string $product = 'claybbs'): ?array
    {
        $this->ensureTable();
        $product = $this->normalizeProduct($product);
        $stmt = Database::connection()->prepare("SELECT * FROM site_limit_requests WHERE user_id=:uid AND product=:product AND status='approved' ORDER BY id DESC LIMIT 1");
        $stmt->execute([':uid' => $userId, ':product' => $product]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(int $userId, string $product, int $currentLimit, int $currentUsed, int $requestedCount, string $reason): int
    {
        $this->ensureTable();
        $product = $this->normalizeProduct($product);
        if ($this->pendingForUser($userId, $product)) {
            throw new \RuntimeException('你已有一条待处理申请，请等待审核。');
        }
        if ($this->approvedForUser($userId, $product)) {
            throw new \RuntimeException('你已成功申请过授权数量增加，不能重复申请。');
        }
        $stmt = Database::connection()->prepare("INSERT INTO site_limit_requests (user_id,product,current_limit,current_used,requested_count,reason,status,created_at,updated_at) VALUES (:uid,:product,:limit,:used,:count,:reason,'pending',NOW(),NOW())");
        $stmt->execute([':uid'=>$userId, ':product'=>$product, ':limit'=>$currentLimit, ':used'=>$currentUsed, ':count'=>$requestedCount, ':reason'=>$reason]);
        return (int)Database::connection()->lastInsertId();
    }

    public function all(string $status = '', string $product = 'claybbs'): array
    {
        $this->ensureTable();
        $product = $this->normalizeProduct($product);
        $where = 'WHERE r.product=:product';
        $params = [':product' => $product];
        $status = strtolower(trim($status));
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) $status = '';
        if ($status !== '') { $where .= ' AND r.status=:status'; $params[':status'] = $status; }
        $stmt = Database::connection()->prepare("SELECT r.*, u.email, u.name, u.site_limit, u.claybbs_site_limit, u.cutot_site_limit FROM site_limit_requests r LEFT JOIN users u ON u.id=r.user_id {$where} ORDER BY FIELD(r.status,'pending','approved','rejected'), r.id DESC LIMIT 300");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByStatus(string $status = '', string $product = 'claybbs'): int
    {
        $this->ensureTable();
        $product = $this->normalizeProduct($product);
        $status = strtolower(trim($status));
        if ($status === '') {
            $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM site_limit_requests WHERE product=:product');
            $stmt->execute([':product' => $product]);
            return (int)$stmt->fetchColumn();
        }
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM site_limit_requests WHERE product=:product AND status=:status');
        $stmt->execute([':product' => $product, ':status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function review(int $id, string $action, int $adminId, string $note = ''): void
    {
        $this->ensureTable();
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT * FROM site_limit_requests WHERE id=:id FOR UPDATE");
            $stmt->execute([':id'=>$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || ($row['status'] ?? '') !== 'pending') {
                $db->commit();
                return;
            }
            $status = $action === 'approve' ? 'approved' : 'rejected';
            if ($status === 'approved') {
                $limitColumn = $this->limitColumn((string)($row['product'] ?? 'claybbs'));
                $db->prepare("UPDATE users SET {$limitColumn}={$limitColumn}+:count WHERE id=:uid")->execute([':count'=>(int)$row['requested_count'], ':uid'=>(int)$row['user_id']]);
            } elseif ($note === '') {
                $note = '申请未通过，管理员未填写具体原因。';
            }
            $db->prepare("UPDATE site_limit_requests SET status=:status, review_note=:note, reviewed_by=:admin, reviewed_at=NOW(), updated_at=NOW() WHERE id=:id")
                ->execute([':status'=>$status, ':note'=>$note, ':admin'=>$adminId ?: null, ':id'=>$id]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function createPurchaseOrder(int $userId, string $product, int $requestedCount, float $unitPrice): array
    {
        $this->ensureTable();
        $product = $this->normalizeProduct($product);
        $requestedCount = max(1, $requestedCount);
        $unitPrice = max(0, round($unitPrice, 2));
        $amount = round($unitPrice * $requestedCount, 2);
        if ($amount <= 0) {
            throw new \RuntimeException('授权购买价格未配置，请联系管理员');
        }
        $old = Database::connection()->prepare("SELECT * FROM site_limit_orders WHERE user_id=:uid AND product=:product AND requested_count=:count AND status='pending' ORDER BY id DESC LIMIT 1");
        $old->execute([':uid'=>$userId, ':product'=>$product, ':count'=>$requestedCount]);
        $row = $old->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
        $orderNo = 'AUTH' . date('YmdHis') . strtoupper(bin2hex(random_bytes(4)));
        Database::connection()->prepare("INSERT INTO site_limit_orders (order_no,user_id,product,requested_count,unit_price,amount,status) VALUES (:order_no,:uid,:product,:count,:unit_price,:amount,'pending')")
            ->execute([':order_no'=>$orderNo, ':uid'=>$userId, ':product'=>$product, ':count'=>$requestedCount, ':unit_price'=>number_format($unitPrice,2,'.',''), ':amount'=>number_format($amount,2,'.','')]);
        return $this->findOrder($orderNo) ?: ['order_no'=>$orderNo,'user_id'=>$userId,'product'=>$product,'requested_count'=>$requestedCount,'unit_price'=>$unitPrice,'amount'=>$amount,'status'=>'pending'];
    }

    public function findOrder(string $orderNo): ?array
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare('SELECT o.*, u.email AS user_email, u.name AS user_name, u.site_limit, u.claybbs_site_limit, u.cutot_site_limit FROM site_limit_orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.order_no=:order_no LIMIT 1');
        $stmt->execute([':order_no'=>$orderNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function ordersByUser(int $userId): array
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare('SELECT * FROM site_limit_orders WHERE user_id=:uid ORDER BY id DESC LIMIT 100');
        $stmt->execute([':uid'=>$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markOrderPaid(string $orderNo, string $tradeNo = ''): ?array
    {
        $this->ensureTable();
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT * FROM site_limit_orders WHERE order_no=:order_no FOR UPDATE");
            $stmt->execute([':order_no'=>$orderNo]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) { $db->commit(); return null; }
            if (($order['status'] ?? '') !== 'paid') {
                $limitColumn = $this->limitColumn((string)($order['product'] ?? 'claybbs'));
                $db->prepare("UPDATE site_limit_orders SET status='paid', trade_no=:trade_no, paid_at=NOW(), updated_at=NOW() WHERE order_no=:order_no AND status='pending'")
                    ->execute([':trade_no'=>$tradeNo !== '' ? $tradeNo : ($order['trade_no'] ?? null), ':order_no'=>$orderNo]);
                $db->prepare("UPDATE users SET {$limitColumn}={$limitColumn}+:count WHERE id=:uid")
                    ->execute([':count'=>(int)$order['requested_count'], ':uid'=>(int)$order['user_id']]);
            }
            $db->commit();
            return $this->findOrder($orderNo);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }


    private function normalizeProduct(string $product): string
    {
        $product = strtolower(trim($product));
        return in_array($product, ['claybbs', 'cutot'], true) ? $product : 'claybbs';
    }

    private function limitColumn(string $product): string
    {
        return $this->normalizeProduct($product) === 'cutot' ? 'cutot_site_limit' : 'claybbs_site_limit';
    }

}
