<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AnnouncementModel
{
    public function ensureTable(): void
    {
        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS announcements (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(200) NOT NULL,
                content TEXT DEFAULT NULL,
                level VARCHAR(20) NOT NULL DEFAULT 'info',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_announce_status (status),
                KEY idx_announce_level (level),
                KEY idx_announce_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function all(): array
    {
        $this->ensureTable();
        return Database::connection()
            ->query("SELECT * FROM announcements ORDER BY sort_order ASC, id DESC")
            ->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function active(): array
    {
        $this->ensureTable();
        return Database::connection()
            ->query("SELECT * FROM announcements WHERE status='active' ORDER BY sort_order ASC, id DESC LIMIT 20")
            ->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare("SELECT * FROM announcements WHERE id=:id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            "INSERT INTO announcements (title, content, level, status, sort_order)
             VALUES (:title, :content, :level, :status, :sort_order)"
        );
        $stmt->execute([
            ':title' => $data['title'],
            ':content' => $data['content'] ?? '',
            ':level' => $data['level'] ?? 'info',
            ':status' => $data['status'] ?? 'active',
            ':sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            "UPDATE announcements
             SET title=:title, content=:content, level=:level, status=:status, sort_order=:sort_order
             WHERE id=:id"
        );
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':content' => $data['content'] ?? '',
            ':level' => $data['level'] ?? 'info',
            ':status' => $data['status'] ?? 'active',
            ':sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function delete(int $id): void
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare("DELETE FROM announcements WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }
}
