<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class MarketModel
{
    public function ensureTables(): void
    {
        $db = Database::connection();
        try { $db->exec("ALTER TABLE users ADD COLUMN developer_level VARCHAR(30) NOT NULL DEFAULT 'none'"); } catch (\Throwable $e) {}

        $db->exec("CREATE TABLE IF NOT EXISTS market_categories (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            name VARCHAR(80) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uk_market_category_type_slug (type, slug),
            KEY idx_market_category_type_status (type, status, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->seedDefaultCategories();

        $db->exec("CREATE TABLE IF NOT EXISTS market_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            category_id BIGINT UNSIGNED DEFAULT NULL,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(120) NOT NULL,
            version VARCHAR(50) NOT NULL DEFAULT '1.0.0',
            description TEXT DEFAULT NULL,
            author VARCHAR(120) DEFAULT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            logo VARCHAR(255) DEFAULT NULL,
            filename VARCHAR(255) NOT NULL,
            hash VARCHAR(128) NOT NULL,
            manifest_json MEDIUMTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            developer_user_id BIGINT UNSIGNED DEFAULT NULL,
            review_note TEXT DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            downloads INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uk_type_slug (type, slug),
            KEY idx_market_items_category (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try { $db->exec("ALTER TABLE market_items ADD COLUMN category_id BIGINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE market_items ADD INDEX idx_market_items_category (category_id)"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE market_items ADD COLUMN logo VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE market_items ADD COLUMN developer_user_id BIGINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE market_items ADD COLUMN review_note TEXT DEFAULT NULL"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE market_items ADD COLUMN reviewed_at DATETIME DEFAULT NULL"); } catch (\Throwable $e) {}
        $this->backfillItemCategories();

        $db->exec("CREATE TABLE IF NOT EXISTS market_versions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            version VARCHAR(50) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            hash VARCHAR(128) NOT NULL,
            manifest_json MEDIUMTEXT DEFAULT NULL,
            changelog TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            review_note TEXT DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_item_status (item_id,status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS market_licenses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key VARCHAR(80) NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED DEFAULT NULL,
            bound_domain VARCHAR(190) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uk_license_key (license_key),
            UNIQUE KEY uk_user_item (user_id,item_id),
            KEY idx_market_licenses_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try { $db->exec("ALTER TABLE market_licenses ADD COLUMN order_id BIGINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $e) {}
        try { $db->exec("ALTER TABLE market_licenses ADD INDEX idx_market_licenses_order (order_id)"); } catch (\Throwable $e) {}

        $db->exec("CREATE TABLE IF NOT EXISTS market_orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_no VARCHAR(64) NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            pay_type VARCHAR(30) NOT NULL DEFAULT 'alipay',
            trade_no VARCHAR(120) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            paid_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uk_market_orders_order_no (order_no),
            KEY idx_market_orders_user_status (user_id,status),
            KEY idx_market_orders_item (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        foreach ([
            'developer_user_id BIGINT UNSIGNED DEFAULT NULL',
            'developer_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00',
            'developer_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'platform_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00'
        ] as $col) { try { $db->exec("ALTER TABLE market_orders ADD COLUMN " . $col); } catch (\Throwable $e) {} }
        try { $db->exec("ALTER TABLE market_orders ADD INDEX idx_market_orders_developer (developer_user_id,status)"); } catch (\Throwable $e) {}


        $db->exec("CREATE TABLE IF NOT EXISTS developer_applications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            apply_type VARCHAR(30) NOT NULL DEFAULT 'public',
            reason TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            review_note TEXT DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_developer_applications_user (user_id,status),
            KEY idx_developer_applications_status (status,id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS developer_orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_no VARCHAR(64) NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            trade_no VARCHAR(120) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            paid_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uk_developer_orders_order_no (order_no),
            KEY idx_developer_orders_user_status (user_id,status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS developer_withdrawals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            account_type VARCHAR(30) NOT NULL DEFAULT 'alipay',
            account_name VARCHAR(80) NOT NULL,
            account_no VARCHAR(120) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            review_note TEXT DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_developer_withdrawals_user (user_id,status),
            KEY idx_developer_withdrawals_status (status,id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS market_acquisitions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id VARCHAR(80) NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            acquired_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uk_site_item (site_id, item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS market_appeals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            developer_user_id BIGINT UNSIGNED NOT NULL,
            reason TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            review_note TEXT DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_market_appeals_status (status, id),
            KEY idx_market_appeals_item (item_id),
            KEY idx_market_appeals_developer (developer_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS market_item_images (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_market_item_images_item (item_id, sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function seedDefaultCategories(): void
    {
        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO market_categories (type, name, slug, sort_order, status)
            VALUES (:type, :name, :slug, :sort_order, 'active')
            ON DUPLICATE KEY UPDATE name=VALUES(name), sort_order=VALUES(sort_order)");
        foreach ([
            ['plugin', '通用插件', 'general', 10],
            ['theme', '通用主题', 'general', 10],
        ] as $row) {
            $stmt->execute([':type'=>$row[0], ':name'=>$row[1], ':slug'=>$row[2], ':sort_order'=>$row[3]]);
        }
    }

    private function backfillItemCategories(): void
    {
        $db = Database::connection();
        foreach (['plugin', 'theme'] as $type) {
            $cat = $this->defaultCategory($type);
            if ($cat) {
                $stmt = $db->prepare("UPDATE market_items SET category_id=:cid WHERE type=:type AND category_id IS NULL");
                $stmt->execute([':cid'=>(int)$cat['id'], ':type'=>$type]);
            }
        }
    }

    public function defaultCategory(string $type): ?array
    {
        $this->ensureCategoryTableOnly();
        $stmt = Database::connection()->prepare("SELECT * FROM market_categories WHERE type=:type ORDER BY sort_order ASC, id ASC LIMIT 1");
        $stmt->execute([':type'=>$type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function ensureCategoryTableOnly(): void
    {
        $db = Database::connection();
        $db->exec("CREATE TABLE IF NOT EXISTS market_categories (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            name VARCHAR(80) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uk_market_category_type_slug (type, slug),
            KEY idx_market_category_type_status (type, status, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->seedDefaultCategories();
    }

    public function categories(?string $type = null, bool $activeOnly = false): array
    {
        $this->ensureCategoryTableOnly();
        $where = [];
        $args = [];
        if ($type !== null && $type !== '') { $where[] = 'type=:type'; $args[':type'] = $type; }
        if ($activeOnly) { $where[] = "status='active'"; }
        $sql = 'SELECT * FROM market_categories' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY type ASC, sort_order ASC, id ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function category(int $id): ?array
    {
        $this->ensureCategoryTableOnly();
        $stmt = Database::connection()->prepare('SELECT * FROM market_categories WHERE id=:id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function categoryForType(int $id, string $type, bool $activeOnly = true): ?array
    {
        $this->ensureCategoryTableOnly();
        $sql = 'SELECT * FROM market_categories WHERE id=:id AND type=:type' . ($activeOnly ? " AND status='active'" : '') . ' LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id'=>$id, ':type'=>$type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function saveCategory(array $data): int
    {
        $this->ensureCategoryTableOnly();
        $id = (int)($data['id'] ?? 0);
        $type = (string)($data['type'] ?? '');
        if (!in_array($type, ['plugin','theme'], true)) throw new \RuntimeException('分类类型无效');
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') throw new \RuntimeException('请填写分类名称');
        $slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($data['slug'] ?? ''));
        if ($slug === '') $slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', strtolower($name)) ?: ('cat-' . bin2hex(random_bytes(3)));
        $sort = max(0, (int)($data['sort_order'] ?? 0));
        $status = in_array(($data['status'] ?? 'active'), ['active','hidden'], true) ? (string)$data['status'] : 'active';
        if ($id > 0) {
            Database::connection()->prepare('UPDATE market_categories SET type=:type,name=:name,slug=:slug,sort_order=:sort,status=:status WHERE id=:id')
                ->execute([':type'=>$type, ':name'=>$name, ':slug'=>$slug, ':sort'=>$sort, ':status'=>$status, ':id'=>$id]);
            return $id;
        }
        Database::connection()->prepare('INSERT INTO market_categories (type,name,slug,sort_order,status) VALUES (:type,:name,:slug,:sort,:status)')
            ->execute([':type'=>$type, ':name'=>$name, ':slug'=>$slug, ':sort'=>$sort, ':status'=>$status]);
        return (int)Database::connection()->lastInsertId();
    }

    public function toggleCategory(int $id, string $status): void
    {
        $this->ensureCategoryTableOnly();
        if (!in_array($status, ['active','hidden'], true)) throw new \RuntimeException('分类状态无效');
        Database::connection()->prepare('UPDATE market_categories SET status=:status WHERE id=:id')->execute([':status'=>$status, ':id'=>$id]);
    }

    public function deleteCategory(int $id): void
    {
        $this->ensureTables();
        $cat = $this->category($id);
        if (!$cat) return;
        $fallback = $this->defaultCategory((string)$cat['type']);
        $fallbackId = ($fallback && (int)$fallback['id'] !== $id) ? (int)$fallback['id'] : null;
        Database::connection()->prepare('UPDATE market_items SET category_id=:fallback WHERE category_id=:id')->execute([':fallback'=>$fallbackId, ':id'=>$id]);
        Database::connection()->prepare('DELETE FROM market_categories WHERE id=:id')->execute([':id'=>$id]);
    }

    public function all(?string $type = null, bool $publishedOnly = true, ?int $categoryId = null): array
    {
        $this->ensureTables();
        $where = [];
        $args = [];
        if ($type) { $where[] = 'market_items.type=:type'; $args[':type'] = $type; }
        if ($categoryId !== null && $categoryId > 0) { $where[] = 'market_items.category_id=:category_id'; $args[':category_id'] = $categoryId; }
        if ($publishedOnly) { $where[] = "market_items.status='published'"; }
        $sql = 'SELECT market_items.*, c.name AS category_name, c.slug AS category_slug, c.status AS category_status, u.name AS developer_name, u.email AS developer_email, u.developer_level FROM market_items LEFT JOIN market_categories c ON c.id=market_items.category_id LEFT JOIN users u ON u.id=market_items.developer_user_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY market_items.id DESC LIMIT 300';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT market_items.*, c.name AS category_name, c.slug AS category_slug, c.status AS category_status, u.name AS developer_name, u.email AS developer_email, u.developer_level FROM market_items LEFT JOIN market_categories c ON c.id=market_items.category_id LEFT JOIN users u ON u.id=market_items.developer_user_id WHERE market_items.id=:id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function upsert(array $data): int
    {
        $this->ensureTables();
        $old = $this->findBySlug($data['type'], $data['slug']);
        $categoryId = (int)($data['category_id'] ?? ($old['category_id'] ?? 0));
        if ($categoryId <= 0) { $categoryId = (int)($this->defaultCategory((string)$data['type'])['id'] ?? 0); }
        if ($old) {
            $stmt = Database::connection()->prepare("UPDATE market_items SET category_id=:category_id, name=:name, version=:version, description=:description, author=:author, price=:price, logo=:logo, filename=:filename, hash=:hash, manifest_json=:manifest_json, status=:status, developer_user_id=:developer_user_id, updated_at=NOW() WHERE id=:id");
            $stmt->execute([
                ':category_id'=>$categoryId ?: null, ':name'=>$data['name'], ':version'=>$data['version'], ':description'=>$data['description'] ?? '', ':author'=>$data['author'] ?? '', ':price'=>$data['price'] ?? 0, ':logo'=>$data['logo'] ?? ($old['logo'] ?? null),
                ':filename'=>$data['filename'], ':hash'=>$data['hash'], ':manifest_json'=>$data['manifest_json'] ?? '{}', ':status'=>$data['status'] ?? 'pending', ':developer_user_id'=>$data['developer_user_id'] ?? ($old['developer_user_id'] ?? null), ':id'=>$old['id']
            ]);
            return (int)$old['id'];
        }
        $stmt = Database::connection()->prepare("INSERT INTO market_items (type,category_id,slug,name,version,description,author,price,logo,filename,hash,manifest_json,status,developer_user_id) VALUES (:type,:category_id,:slug,:name,:version,:description,:author,:price,:logo,:filename,:hash,:manifest_json,:status,:developer_user_id)");
        $stmt->execute([
            ':type'=>$data['type'], ':category_id'=>$categoryId ?: null, ':slug'=>$data['slug'], ':name'=>$data['name'], ':version'=>$data['version'], ':description'=>$data['description'] ?? '', ':author'=>$data['author'] ?? '', ':price'=>$data['price'] ?? 0, ':logo'=>$data['logo'] ?? null,
            ':filename'=>$data['filename'], ':hash'=>$data['hash'], ':manifest_json'=>$data['manifest_json'] ?? '{}', ':status'=>$data['status'] ?? 'pending', ':developer_user_id'=>$data['developer_user_id'] ?? null
        ]);
        return (int)Database::connection()->lastInsertId();
    }

    public function findBySlug(string $type, string $slug): ?array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT * FROM market_items WHERE type=:type AND slug=:slug LIMIT 1');
        $stmt->execute([':type'=>$type, ':slug'=>$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createApp(array $data): int
    {
        $this->ensureTables();
        if ($this->findBySlug($data['type'], $data['slug'])) {
            throw new \RuntimeException('应用 slug 已存在');
        }
        $categoryId = (int)($data['category_id'] ?? 0);
        $category = $this->categoryForType($categoryId, (string)$data['type'], true);
        if (!$category) throw new \RuntimeException('请选择有效分类');
        $stmt = Database::connection()->prepare("INSERT INTO market_items (type,category_id,slug,name,version,description,author,price,logo,filename,hash,manifest_json,status,developer_user_id) VALUES (:type,:category_id,:slug,:name,'0.0.0',:description,:author,:price,:logo,'','',:manifest_json,'draft',:developer_user_id)");
        $manifest = ['type'=>$data['type'], 'category'=>$category['slug'], 'slug'=>$data['slug'], 'name'=>$data['name'], 'description'=>$data['description'] ?? '', 'author'=>$data['author'] ?? '', 'price'=>$data['price'] ?? 0];
        $stmt->execute([
            ':type'=>$data['type'], ':category_id'=>$categoryId, ':slug'=>$data['slug'], ':name'=>$data['name'], ':description'=>$data['description'] ?? '', ':author'=>$data['author'] ?? '', ':price'=>$data['price'] ?? 0, ':logo'=>$data['logo'] ?? null,
            ':manifest_json'=>json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), ':developer_user_id'=>$data['developer_user_id'] ?? null,
        ]);
        return (int)Database::connection()->lastInsertId();
    }

    public function updateApp(int $id, int $userId, array $data): void
    {
        $this->ensureTables();
        $item = $this->find($id);
        if (!$item || (int)($item['developer_user_id'] ?? 0) !== $userId) throw new \RuntimeException('应用不存在或无权限');
        $categoryId = (int)($data['category_id'] ?? 0);
        $category = $this->categoryForType($categoryId, (string)$item['type'], true);
        if (!$category) throw new \RuntimeException('请选择有效分类');
        $manifest = json_decode((string)($item['manifest_json'] ?? '{}'), true) ?: [];
        $manifest['category'] = $category['slug'];
        $manifest['name'] = $data['name'];
        $manifest['description'] = $data['description'] ?? '';
        $manifest['author'] = $data['author'] ?? '';
        $manifest['price'] = $data['price'] ?? 0;
        Database::connection()->prepare('UPDATE market_items SET category_id=:category_id, name=:name, description=:description, author=:author, price=:price, logo=:logo, manifest_json=:manifest_json, updated_at=NOW() WHERE id=:id')
            ->execute([':category_id'=>$categoryId, ':name'=>$data['name'], ':description'=>$data['description'] ?? '', ':author'=>$data['author'] ?? '', ':price'=>$data['price'] ?? 0, ':logo'=>$data['logo'] ?? ($item['logo'] ?? null), ':manifest_json'=>json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), ':id'=>$id]);
    }

    public function createVersion(int $itemId, array $data): int
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare("INSERT INTO market_versions (item_id,version,filename,hash,manifest_json,changelog,status) VALUES (:item_id,:version,:filename,:hash,:manifest_json,:changelog,'pending')");
        $stmt->execute([':item_id'=>$itemId, ':version'=>$data['version'], ':filename'=>$data['filename'], ':hash'=>$data['hash'], ':manifest_json'=>$data['manifest_json'] ?? '{}', ':changelog'=>$data['changelog'] ?? '']);
        $item = $this->find($itemId);
        if (($item['status'] ?? '') === 'published') {
            Database::connection()->prepare("UPDATE market_items SET updated_at=NOW() WHERE id=:id")->execute([':id'=>$itemId]);
        } else {
            Database::connection()->prepare("UPDATE market_items SET status='pending', version=:version, filename=:filename, hash=:hash, manifest_json=:manifest_json, updated_at=NOW() WHERE id=:id")->execute([':version'=>$data['version'], ':filename'=>$data['filename'], ':hash'=>$data['hash'], ':manifest_json'=>$data['manifest_json'] ?? '{}', ':id'=>$itemId]);
        }
        return (int)Database::connection()->lastInsertId();
    }

    public function versionsByItem(int $itemId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT * FROM market_versions WHERE item_id=:id ORDER BY id DESC LIMIT 100');
        $stmt->execute([':id'=>$itemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function pendingVersions(): array
    {
        $this->ensureTables();
        $sql = "SELECT v.*, i.name, i.slug, i.type, i.developer_user_id, c.name AS category_name FROM market_versions v LEFT JOIN market_items i ON i.id=v.item_id LEFT JOIN market_categories c ON c.id=i.category_id WHERE v.status='pending' ORDER BY v.id DESC LIMIT 300";
        return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function reviewVersion(int $versionId, string $status, string $note = ''): void
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT * FROM market_versions WHERE id=:id LIMIT 1');
        $stmt->execute([':id'=>$versionId]);
        $v = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$v) throw new \RuntimeException('版本不存在');
        Database::connection()->prepare('UPDATE market_versions SET status=:status, review_note=:note, reviewed_at=NOW() WHERE id=:id')->execute([':status'=>$status, ':note'=>$note, ':id'=>$versionId]);
        if ($status === 'published') {
            Database::connection()->prepare("UPDATE market_items SET version=:version, filename=:filename, hash=:hash, manifest_json=:manifest_json, status='published', review_note=:note, reviewed_at=NOW(), updated_at=NOW() WHERE id=:item_id")->execute([':version'=>$v['version'], ':filename'=>$v['filename'], ':hash'=>$v['hash'], ':manifest_json'=>$v['manifest_json'], ':note'=>$note, ':item_id'=>$v['item_id']]);
        } elseif ($status === 'rejected') {
            $hasPublished = Database::connection()->prepare("SELECT COUNT(*) FROM market_versions WHERE item_id=:item_id AND status='published'");
            $hasPublished->execute([':item_id'=>$v['item_id']]);
            if ((int)$hasPublished->fetchColumn() > 0) {
                Database::connection()->prepare("UPDATE market_items SET review_note=:note, reviewed_at=NOW() WHERE id=:item_id")->execute([':note'=>$note, ':item_id'=>$v['item_id']]);
            } else {
                Database::connection()->prepare("UPDATE market_items SET status='rejected', review_note=:note, reviewed_at=NOW() WHERE id=:item_id AND status='pending'")->execute([':note'=>$note, ':item_id'=>$v['item_id']]);
            }
        }
    }

    public function allByDeveloperPublic(int $userId, ?string $type = null): array
    {
        $this->ensureTables();
        $args = [':uid'=>$userId];
        $where = "market_items.developer_user_id=:uid AND market_items.status='published'";
        if ($type !== null && $type !== '') { $where .= ' AND market_items.type=:type'; $args[':type']=$type; }
        $stmt = Database::connection()->prepare('SELECT market_items.*, c.name AS category_name, c.slug AS category_slug FROM market_items LEFT JOIN market_categories c ON c.id=market_items.category_id WHERE ' . $where . ' ORDER BY market_items.id DESC LIMIT 300');
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function allByDeveloper(int $userId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT market_items.*, c.name AS category_name, c.slug AS category_slug FROM market_items LEFT JOIN market_categories c ON c.id=market_items.category_id WHERE developer_user_id=:uid ORDER BY market_items.id DESC LIMIT 300');
        $stmt->execute([':uid'=>$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function review(int $id, string $status, string $note = ''): void
    {
        $this->ensureTables();
        Database::connection()->prepare('UPDATE market_items SET status=:status, review_note=:note, reviewed_at=NOW() WHERE id=:id')->execute([':status'=>$status, ':note'=>$note, ':id'=>$id]);
    }

    public function toggle(int $id, string $status): void
    {
        $this->ensureTables();
        Database::connection()->prepare('UPDATE market_items SET status=:status WHERE id=:id')->execute([':status'=>$status, ':id'=>$id]);
    }

    public function delete(int $id): void
    {
        $this->ensureTables();
        Database::connection()->prepare('DELETE FROM market_acquisitions WHERE item_id=:id')->execute([':id'=>$id]);
        Database::connection()->prepare('DELETE FROM market_licenses WHERE item_id=:id')->execute([':id'=>$id]);
        Database::connection()->prepare('DELETE FROM market_orders WHERE item_id=:id')->execute([':id'=>$id]);
        Database::connection()->prepare('DELETE FROM market_versions WHERE item_id=:id')->execute([':id'=>$id]);
        Database::connection()->prepare('DELETE FROM market_items WHERE id=:id')->execute([':id'=>$id]);
    }



    public function imagesByItem(int $itemId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT * FROM market_item_images WHERE item_id=:item_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute([':item_id'=>$itemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function imageMapByItems(array $itemIds): array
    {
        $this->ensureTables();
        $ids = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
        if (!$ids) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare("SELECT * FROM market_item_images WHERE item_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC");
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $map[(int)$row['item_id']][] = $row;
        }
        return $map;
    }

    public function addImages(int $itemId, int $developerUserId, array $paths): void
    {
        $this->ensureTables();
        $item = $this->find($itemId);
        if (!$item || (int)($item['developer_user_id'] ?? 0) !== $developerUserId) throw new \RuntimeException('应用不存在或无权限');
        $paths = array_values(array_filter(array_map('strval', $paths)));
        if (!$paths) return;
        $countStmt = Database::connection()->prepare('SELECT COUNT(*) FROM market_item_images WHERE item_id=:item_id');
        $countStmt->execute([':item_id'=>$itemId]);
        $baseSort = (int)$countStmt->fetchColumn();
        $stmt = Database::connection()->prepare('INSERT INTO market_item_images (item_id, image_path, sort_order) VALUES (:item_id, :image_path, :sort_order)');
        foreach ($paths as $idx => $path) {
            $stmt->execute([':item_id'=>$itemId, ':image_path'=>$path, ':sort_order'=>$baseSort + $idx + 1]);
        }
    }

    public function deleteImages(int $itemId, int $developerUserId, array $imageIds): array
    {
        $this->ensureTables();
        $item = $this->find($itemId);
        if (!$item || (int)($item['developer_user_id'] ?? 0) !== $developerUserId) throw new \RuntimeException('应用不存在或无权限');
        $ids = array_values(array_unique(array_filter(array_map('intval', $imageIds))));
        if (!$ids) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare("SELECT id,image_path FROM market_item_images WHERE item_id=? AND id IN ({$placeholders})");
        $stmt->execute(array_merge([$itemId], $ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) return [];
        $delete = Database::connection()->prepare("DELETE FROM market_item_images WHERE item_id=? AND id IN ({$placeholders})");
        $delete->execute(array_merge([$itemId], array_map(fn($r)=>(int)$r['id'], $rows)));
        return array_map(fn($r)=>(string)$r['image_path'], $rows);
    }

    public function createAppeal(int $itemId, int $developerUserId, string $reason): int
    {
        $this->ensureTables();
        $item = $this->find($itemId);
        if (!$item || (int)($item['developer_user_id'] ?? 0) !== $developerUserId) throw new \RuntimeException('应用不存在或无权限');
        if (($item['status'] ?? '') !== 'hidden') throw new \RuntimeException('只有已下架应用可以提交申诉');
        $reason = trim($reason);
        if ($reason === '') throw new \RuntimeException('请填写申诉理由');
        $stmt = Database::connection()->prepare("SELECT id FROM market_appeals WHERE item_id=:item_id AND developer_user_id=:uid AND status='pending' LIMIT 1");
        $stmt->execute([':item_id'=>$itemId, ':uid'=>$developerUserId]);
        if ($stmt->fetchColumn()) throw new \RuntimeException('该应用已有待处理申诉，请等待后台处理');
        Database::connection()->prepare("INSERT INTO market_appeals (item_id, developer_user_id, reason, status) VALUES (:item_id, :uid, :reason, 'pending')")
            ->execute([':item_id'=>$itemId, ':uid'=>$developerUserId, ':reason'=>$reason]);
        return (int)Database::connection()->lastInsertId();
    }

    public function appealsByDeveloper(int $developerUserId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare("SELECT a.*, i.name, i.slug, i.type, i.status AS item_status FROM market_appeals a LEFT JOIN market_items i ON i.id=a.item_id WHERE a.developer_user_id=:uid ORDER BY a.id DESC LIMIT 200");
        $stmt->execute([':uid'=>$developerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function appealMapByDeveloper(int $developerUserId): array
    {
        $rows = $this->appealsByDeveloper($developerUserId);
        $map = [];
        foreach ($rows as $row) {
            $itemId = (int)($row['item_id'] ?? 0);
            if ($itemId > 0 && !isset($map[$itemId])) $map[$itemId] = $row;
        }
        return $map;
    }

    public function appeals(bool $pendingOnly = false): array
    {
        $this->ensureTables();
        $where = $pendingOnly ? " WHERE a.status='pending'" : '';
        $sql = "SELECT a.*, i.name, i.slug, i.type, i.version, i.status AS item_status, c.name AS category_name, u.name AS developer_name, u.email AS developer_email FROM market_appeals a LEFT JOIN market_items i ON i.id=a.item_id LEFT JOIN market_categories c ON c.id=i.category_id LEFT JOIN users u ON u.id=a.developer_user_id" . $where . " ORDER BY a.id DESC LIMIT 300";
        return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function reviewAppeal(int $appealId, string $status, string $note = ''): void
    {
        $this->ensureTables();
        if (!in_array($status, ['approved','rejected'], true)) throw new \RuntimeException('申诉处理状态无效');
        $stmt = Database::connection()->prepare("SELECT * FROM market_appeals WHERE id=:id LIMIT 1");
        $stmt->execute([':id'=>$appealId]);
        $appeal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$appeal) throw new \RuntimeException('申诉不存在');
        if (($appeal['status'] ?? '') !== 'pending') throw new \RuntimeException('该申诉已处理');
        $db = Database::connection();
        $db->prepare("UPDATE market_appeals SET status=:status, review_note=:note, reviewed_at=NOW() WHERE id=:id")
            ->execute([':status'=>$status, ':note'=>$note, ':id'=>$appealId]);
        if ($status === 'approved') {
            $db->prepare("UPDATE market_items SET status='published', review_note=:note, reviewed_at=NOW(), updated_at=NOW() WHERE id=:id")
                ->execute([':note'=>$note !== '' ? $note : '申诉通过，应用已恢复上架', ':id'=>(int)$appeal['item_id']]);
        }
    }

    public function licenseForUser(int $userId, int $itemId): ?array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT l.*, i.name, i.type, i.slug, i.version, i.price FROM market_licenses l LEFT JOIN market_items i ON i.id=l.item_id WHERE l.user_id=:uid AND l.item_id=:item LIMIT 1');
        $stmt->execute([':uid'=>$userId, ':item'=>$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createLicense(int $userId, int $itemId, ?int $orderId = null): array
    {
        $this->ensureTables();
        $old = $this->licenseForUser($userId, $itemId);
        if ($old) return $old;
        $key = 'APP-' . strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        Database::connection()->prepare('INSERT INTO market_licenses (license_key,user_id,item_id,order_id) VALUES (:k,:uid,:item,:order_id)')->execute([':k'=>$key, ':uid'=>$userId, ':item'=>$itemId, ':order_id'=>$orderId]);
        return $this->licenseForUser($userId, $itemId) ?: ['license_key'=>$key, 'user_id'=>$userId, 'item_id'=>$itemId, 'order_id'=>$orderId];
    }

    public function licensesByUser(int $userId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT l.*, i.name, i.type, i.slug, i.version, i.price, i.description FROM market_licenses l LEFT JOIN market_items i ON i.id=l.item_id WHERE l.user_id=:uid ORDER BY l.id DESC');
        $stmt->execute([':uid'=>$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findLicense(string $key): ?array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT l.*, i.name, i.type, i.slug, i.version, i.price, i.filename, i.hash, i.status AS item_status FROM market_licenses l LEFT JOIN market_items i ON i.id=l.item_id WHERE l.license_key=:k LIMIT 1');
        $stmt->execute([':k'=>$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function bindLicense(string $key, int $userId, string $domain): void
    {
        $this->ensureTables();
        Database::connection()->prepare('UPDATE market_licenses SET bound_domain=:d WHERE license_key=:k AND user_id=:uid')->execute([':d'=>$domain, ':k'=>$key, ':uid'=>$userId]);
    }

    public function unbindLicense(string $key, int $userId): void
    {
        $this->ensureTables();
        Database::connection()->prepare('UPDATE market_licenses SET bound_domain=NULL WHERE license_key=:k AND user_id=:uid')->execute([':k'=>$key, ':uid'=>$userId]);
    }



    public function grantLicenseToUser(int $userId, int $itemId, string $domain = ''): array
    {
        $this->ensureTables();
        $item = $this->find($itemId);
        if (!$item) throw new \RuntimeException('应用不存在');
        $license = $this->createLicense($userId, $itemId, null);
        $domain = trim($domain);
        if ($domain !== '') {
            Database::connection()->prepare('UPDATE market_licenses SET bound_domain=:domain WHERE id=:id')
                ->execute([':domain'=>$domain, ':id'=>(int)$license['id']]);
            $license = $this->licenseForUser($userId, $itemId) ?: $license;
        }
        return $license;
    }

    public function unbindLicenseById(int $licenseId): void
    {
        $this->ensureTables();
        Database::connection()->prepare('UPDATE market_licenses SET bound_domain=NULL WHERE id=:id')->execute([':id'=>$licenseId]);
    }

    public function licenses(array $filters = []): array
    {
        $this->ensureTables();
        $where = [];
        $args = [];
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(l.license_key LIKE :q OR l.bound_domain LIKE :q OR i.name LIKE :q OR i.slug LIKE :q OR u.email LIKE :q OR u.name LIKE :q)';
            $args[':q'] = '%' . $q . '%';
        }
        $sql = 'SELECT l.*, i.name, i.type, i.slug, i.version, i.price, u.email AS user_email, u.name AS user_name FROM market_licenses l LEFT JOIN market_items i ON i.id=l.item_id LEFT JOIN users u ON u.id=l.user_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY l.id DESC LIMIT 300';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createOrder(int $userId, int $itemId): array
    {
        $this->ensureTables();
        $item = $this->find($itemId);
        if (!$item || ($item['status'] ?? '') !== 'published') {
            throw new \RuntimeException('应用不存在或未上架');
        }
        $amount = round((float)($item['price'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \RuntimeException('免费应用无需创建支付订单');
        }
        $old = $this->pendingOrderForUser($userId, $itemId);
        if ($old) return $old;
        $orderNo = 'MO' . date('YmdHis') . strtoupper(bin2hex(random_bytes(4)));
        Database::connection()->prepare("INSERT INTO market_orders (order_no,user_id,item_id,developer_user_id,amount,pay_type,status) VALUES (:order_no,:user_id,:item_id,:developer_user_id,:amount,'alipay','pending')")
            ->execute([':order_no'=>$orderNo, ':user_id'=>$userId, ':item_id'=>$itemId, ':developer_user_id'=>(int)($item['developer_user_id'] ?? 0) ?: null, ':amount'=>number_format($amount, 2, '.', '')]);
        return $this->findOrderByNo($orderNo) ?: ['order_no'=>$orderNo, 'user_id'=>$userId, 'item_id'=>$itemId, 'amount'=>$amount, 'status'=>'pending'];
    }

    public function pendingOrderForUser(int $userId, int $itemId): ?array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare("SELECT o.*, i.name, i.type, i.slug, i.version, i.price FROM market_orders o LEFT JOIN market_items i ON i.id=o.item_id WHERE o.user_id=:uid AND o.item_id=:item AND o.status='pending' ORDER BY o.id DESC LIMIT 1");
        $stmt->execute([':uid'=>$userId, ':item'=>$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findOrderByNo(string $orderNo): ?array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT o.*, i.name, i.type, i.slug, i.version, i.price, i.status AS item_status, u.email AS user_email, u.name AS user_name, l.license_key, l.bound_domain, l.status AS license_status FROM market_orders o LEFT JOIN market_items i ON i.id=o.item_id LEFT JOIN users u ON u.id=o.user_id LEFT JOIN market_licenses l ON l.order_id=o.id OR (l.user_id=o.user_id AND l.item_id=o.item_id) WHERE o.order_no=:order_no LIMIT 1');
        $stmt->execute([':order_no'=>$orderNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markOrderPaid(string $orderNo, string $tradeNo = ''): ?array
    {
        $this->ensureTables();
        $db = Database::connection();
        $order = $this->findOrderByNo($orderNo);
        if (!$order) return null;
        if (($order['status'] ?? '') !== 'paid') {
            $rate = $this->developerShareRatio();
            $developerId = (int)($order['developer_user_id'] ?? 0);
            if ($developerId <= 0) { $item = $this->find((int)$order['item_id']); $developerId = (int)($item['developer_user_id'] ?? 0); }
            $devAmount = $developerId > 0 ? round((float)$order['amount'] * $rate / 100, 2) : 0.0;
            $platformAmount = round((float)$order['amount'] - $devAmount, 2);
            $stmt = $db->prepare("UPDATE market_orders SET status='paid', trade_no=:trade_no, developer_user_id=:developer_user_id, developer_rate=:developer_rate, developer_amount=:developer_amount, platform_amount=:platform_amount, paid_at=NOW(), updated_at=NOW() WHERE order_no=:order_no AND status='pending'");
            $stmt->execute([':trade_no'=>$tradeNo !== '' ? $tradeNo : ($order['trade_no'] ?? null), ':developer_user_id'=>$developerId ?: null, ':developer_rate'=>$rate, ':developer_amount'=>$devAmount, ':platform_amount'=>$platformAmount, ':order_no'=>$orderNo]);
        }
        $order = $this->findOrderByNo($orderNo);
        if ($order && ($order['status'] ?? '') === 'paid') {
            $this->createLicense((int)$order['user_id'], (int)$order['item_id'], (int)$order['id']);
        }
        return $order;
    }

    public function ordersByUser(int $userId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT o.*, i.name, i.type, i.slug, i.version, i.price FROM market_orders o LEFT JOIN market_items i ON i.id=o.item_id WHERE o.user_id=:uid ORDER BY o.id DESC LIMIT 100');
        $stmt->execute([':uid'=>$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function orders(array $filters = []): array
    {
        $this->ensureTables();
        $where = [];
        $args = [];
        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') { $where[] = 'o.status=:status'; $args[':status'] = $status; }
        $type = trim((string)($filters['type'] ?? ''));
        if (in_array($type, ['plugin','theme'], true)) { $where[] = 'i.type=:type'; $args[':type'] = $type; }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(o.order_no LIKE :q OR o.trade_no LIKE :q OR i.name LIKE :q OR i.slug LIKE :q OR u.email LIKE :q OR u.name LIKE :q OR l.license_key LIKE :q)';
            $args[':q'] = '%' . $q . '%';
        }
        $sql = 'SELECT o.*, i.name, i.type, i.slug, i.version, i.price, u.email AS user_email, u.name AS user_name, l.license_key, l.bound_domain, l.status AS license_status FROM market_orders o LEFT JOIN market_items i ON i.id=o.item_id LEFT JOIN users u ON u.id=o.user_id LEFT JOIN market_licenses l ON l.order_id=o.id OR (l.user_id=o.user_id AND l.item_id=o.item_id)' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.id DESC LIMIT 300';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function orderStats(): array
    {
        $this->ensureTables();
        $rows = Database::connection()->query("SELECT status, COUNT(*) AS c, COALESCE(SUM(amount),0) AS total FROM market_orders GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stats = ['pending'=>['count'=>0,'total'=>0.0], 'paid'=>['count'=>0,'total'=>0.0], 'closed'=>['count'=>0,'total'=>0.0]];
        foreach ($rows as $row) {
            $key = (string)$row['status'];
            $stats[$key] = ['count'=>(int)$row['c'], 'total'=>(float)$row['total']];
        }
        return $stats;
    }

    public function closeOrder(string $orderNo): void
    {
        $this->ensureTables();
        Database::connection()->prepare("UPDATE market_orders SET status='closed', updated_at=NOW() WHERE order_no=:order_no AND status='pending'")->execute([':order_no'=>$orderNo]);
    }


    private function developerShareRatio(): float
    {
        try { $val = (new SettingModel())->get('developer_share_ratio', '70'); } catch (\Throwable $e) { $val = '70'; }
        return max(0, min(100, (float)$val));
    }

    public function developerSales(int $developerId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare("SELECT o.*, i.name, i.type, i.slug, u.email AS buyer_email, u.name AS buyer_name FROM market_orders o LEFT JOIN market_items i ON i.id=o.item_id LEFT JOIN users u ON u.id=o.user_id WHERE o.developer_user_id=:uid AND o.status='paid' ORDER BY o.id DESC LIMIT 300");
        $stmt->execute([':uid'=>$developerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function developerBalance(int $developerId): array
    {
        $this->ensureTables();
        $st = Database::connection()->prepare("SELECT COALESCE(SUM(developer_amount),0) FROM market_orders WHERE developer_user_id=:uid AND status='paid'");
        $st->execute([':uid'=>$developerId]);
        $income = (float)$st->fetchColumn();
        $st = Database::connection()->prepare("SELECT COALESCE(SUM(amount),0) FROM developer_withdrawals WHERE user_id=:uid AND status IN ('pending','paid')");
        $st->execute([':uid'=>$developerId]);
        $withdrawn = (float)$st->fetchColumn();
        return ['income'=>$income, 'withdrawn'=>$withdrawn, 'available'=>max(0, round($income-$withdrawn, 2))];
    }

    public function createWithdrawal(int $developerId, float $amount, string $accountName, string $accountNo): void
    {
        $this->ensureTables();
        $balance = $this->developerBalance($developerId);
        $min = (float)(new SettingModel())->get('developer_min_withdraw', '10');
        if ($amount < $min) throw new \RuntimeException('提现金额不能低于 ￥' . number_format($min, 2));
        if ($amount > (float)$balance['available']) throw new \RuntimeException('可提现余额不足');
        if (trim($accountName)==='' || trim($accountNo)==='') throw new \RuntimeException('请填写收款姓名和账号');
        Database::connection()->prepare("INSERT INTO developer_withdrawals (user_id,amount,account_name,account_no) VALUES (:uid,:amount,:name,:no)")->execute([':uid'=>$developerId, ':amount'=>number_format($amount,2,'.',''), ':name'=>trim($accountName), ':no'=>trim($accountNo)]);
    }

    public function withdrawals(?int $developerId = null): array
    {
        $this->ensureTables();
        $where = $developerId ? ' WHERE w.user_id=:uid' : '';
        $stmt = Database::connection()->prepare('SELECT w.*, u.email AS user_email, u.name AS user_name FROM developer_withdrawals w LEFT JOIN users u ON u.id=w.user_id' . $where . ' ORDER BY w.id DESC LIMIT 300');
        $stmt->execute($developerId ? [':uid'=>$developerId] : []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function reviewWithdrawal(int $id, string $status, string $note=''): void
    {
        $this->ensureTables();
        if (!in_array($status, ['paid','rejected'], true)) throw new \RuntimeException('提现状态无效');
        Database::connection()->prepare("UPDATE developer_withdrawals SET status=:status, review_note=:note, reviewed_at=NOW(), updated_at=NOW() WHERE id=:id AND status='pending'")->execute([':status'=>$status, ':note'=>$note, ':id'=>$id]);
    }


    public function developerApplicationForUser(int $userId): ?array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare("SELECT * FROM developer_applications WHERE user_id=:uid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':uid'=>$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createDeveloperApplication(int $userId, string $reason = ''): void
    {
        $this->ensureTables();
        $old = $this->developerApplicationForUser($userId);
        if ($old && ($old['status'] ?? '') === 'pending') throw new \RuntimeException('你已经提交过公益开发者申请，请等待审核');
        Database::connection()->prepare("INSERT INTO developer_applications (user_id,apply_type,reason,status) VALUES (:uid,'public',:reason,'pending')")->execute([':uid'=>$userId, ':reason'=>trim($reason)]);
    }

    public function developerApplications(): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->query("SELECT a.*, u.email AS user_email, u.name AS user_name, u.role, u.developer_level FROM developer_applications a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 300");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function reviewDeveloperApplication(int $id, string $status, string $note = ''): void
    {
        $this->ensureTables();
        if (!in_array($status, ['approved','rejected'], true)) throw new \RuntimeException('审核状态无效');
        $stmt = Database::connection()->prepare('SELECT * FROM developer_applications WHERE id=:id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$app) throw new \RuntimeException('申请不存在');
        Database::connection()->prepare("UPDATE developer_applications SET status=:status, review_note=:note, reviewed_at=NOW(), updated_at=NOW() WHERE id=:id")->execute([':status'=>$status, ':note'=>$note, ':id'=>$id]);
        if ($status === 'approved') {
            Database::connection()->prepare("UPDATE users SET role='developer', developer_level='public' WHERE id=:uid AND role='user'")->execute([':uid'=>(int)$app['user_id']]);
        }
    }

    public function developerJoinOrder(int $userId): array
    {
        $this->ensureTables();
        $old = Database::connection()->prepare("SELECT * FROM developer_orders WHERE user_id=:uid AND status='pending' ORDER BY id DESC LIMIT 1");
        $old->execute([':uid'=>$userId]);
        $row = $old->fetch(PDO::FETCH_ASSOC); if ($row) return $row;
        $amount = max(0, (float)(new SettingModel())->get('developer_join_price','99.00'));
        $orderNo = 'DEV' . date('YmdHis') . strtoupper(bin2hex(random_bytes(4)));
        Database::connection()->prepare("INSERT INTO developer_orders (order_no,user_id,amount) VALUES (:order_no,:uid,:amount)")->execute([':order_no'=>$orderNo, ':uid'=>$userId, ':amount'=>number_format($amount,2,'.','')]);
        return $this->findDeveloperOrder($orderNo) ?: ['order_no'=>$orderNo,'user_id'=>$userId,'amount'=>$amount,'status'=>'pending'];
    }

    public function findDeveloperOrder(string $orderNo): ?array
    {
        $this->ensureTables();
        $st=Database::connection()->prepare('SELECT d.*, u.email AS user_email, u.name AS user_name, u.role, u.developer_level FROM developer_orders d LEFT JOIN users u ON u.id=d.user_id WHERE d.order_no=:order_no LIMIT 1');
        $st->execute([':order_no'=>$orderNo]);
        $row=$st->fetch(PDO::FETCH_ASSOC); return $row?:null;
    }




    public function developerOrdersByUser(int $userId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT * FROM developer_orders WHERE user_id=:uid ORDER BY id DESC LIMIT 100');
        $stmt->execute([':uid'=>$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function developerJoinOrders(array $filters = []): array
    {
        $this->ensureTables();
        $where = [];
        $args = [];
        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') { $where[] = 'd.status=:status'; $args[':status'] = $status; }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(d.order_no LIKE :q OR d.trade_no LIKE :q OR u.email LIKE :q OR u.name LIKE :q OR CAST(u.id AS CHAR)=:id_exact)';
            $args[':q'] = '%' . $q . '%';
            $args[':id_exact'] = ctype_digit($q) ? $q : '-1';
        }
        $sql = 'SELECT d.*, u.email AS user_email, u.name AS user_name, u.role, u.developer_level FROM developer_orders d LEFT JOIN users u ON u.id=d.user_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY d.id DESC LIMIT 300';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function developerOrderStats(): array
    {
        $this->ensureTables();
        $rows = Database::connection()->query("SELECT status, COUNT(*) AS c, COALESCE(SUM(amount),0) AS total FROM developer_orders GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stats = ['pending'=>['count'=>0,'total'=>0.0], 'paid'=>['count'=>0,'total'=>0.0], 'closed'=>['count'=>0,'total'=>0.0]];
        foreach ($rows as $row) $stats[(string)$row['status']] = ['count'=>(int)$row['c'], 'total'=>(float)$row['total']];
        return $stats;
    }

    public function closeDeveloperOrder(string $orderNo): void
    {
        $this->ensureTables();
        Database::connection()->prepare("UPDATE developer_orders SET status='closed', updated_at=NOW() WHERE order_no=:order_no AND status='pending'")->execute([':order_no'=>$orderNo]);
    }

    public function makePublicDeveloper(int $userId): void
    {
        $this->ensureTables();
        Database::connection()->prepare("UPDATE users SET role='developer', developer_level='public' WHERE id=:uid AND role='user'")->execute([':uid'=>$userId]);
    }

    public function markDeveloperOrderPaid(string $orderNo, string $tradeNo=''): ?array
    {
        $this->ensureTables();
        $order=$this->findDeveloperOrder($orderNo); if(!$order) return null;
        if (($order['status']??'')!=='paid') Database::connection()->prepare("UPDATE developer_orders SET status='paid', trade_no=:trade_no, paid_at=NOW(), updated_at=NOW() WHERE order_no=:order_no AND status='pending'")->execute([':trade_no'=>$tradeNo!==''?$tradeNo:($order['trade_no']??null), ':order_no'=>$orderNo]);
        $order=$this->findDeveloperOrder($orderNo);
        if($order && ($order['status']??'')==='paid') Database::connection()->prepare("UPDATE users SET role='developer', developer_level='normal' WHERE id=:uid AND (role='user' OR developer_level='public')")->execute([':uid'=>(int)$order['user_id']]);
        return $order;
    }

    public function acquire(string $siteId, int $itemId, ?int $userId = null): void
    {
        $this->ensureTables();
        Database::connection()->prepare('INSERT IGNORE INTO market_acquisitions (site_id,item_id,user_id) VALUES (:site_id,:item_id,:user_id)')->execute([':site_id'=>$siteId, ':item_id'=>$itemId, ':user_id'=>$userId]);
    }

    public function acquiredIds(string $siteId): array
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('SELECT item_id FROM market_acquisitions WHERE site_id=:site_id');
        $stmt->execute([':site_id'=>$siteId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function incrementDownload(int $id): void
    {
        $this->ensureTables();
        Database::connection()->prepare('UPDATE market_items SET downloads=downloads+1 WHERE id=:id')->execute([':id'=>$id]);
    }
}
